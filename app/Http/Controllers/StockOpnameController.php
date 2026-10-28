<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(private readonly StockOpnameService $opname)
    {
    }

    /**
     * Form opname minggu berjalan plus riwayat.
     *
     * Manajer selalu diarahkan ke outlet sendiri. Owner boleh memilih outlet
     * dan melihat status kepatuhan semua outlet.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockOpname::class);

        $user = $request->user();
        $isOwner = $user->isOwner();

        [$weekStart, $weekEnd] = StockOpname::currentWeek();
        $weekStartDate = $weekStart->toDateString();

        $requestedOutlet = $request->integer('outlet') ?: null;

        $outletId = $isOwner
            ? ($requestedOutlet ?? Outlet::active()->ordered()->value('id'))
            : (int) $user->outlet_id;

        $outlet = $outletId !== null ? Outlet::find($outletId) : null;

        $existing = $outletId !== null
            ? StockOpname::with(['user:id,name', 'items.product:id,name,sku,unit'])
                ->forOutlet($outletId)
                ->forWeek($weekStartDate)
                ->first()
            : null;

        // Produk aktif dengan stok sistem saat ini, dipakai mengisi form.
        $products = ($outletId !== null && $existing === null)
            ? Product::active()->ordered()->withStock($outletId)->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'system_qty' => (int) ($product->stock_qty ?? 0),
            ])->all()
            : [];

        $historyQuery = StockOpname::query()
            ->with(['outlet:id,name,code', 'user:id,name'])
            ->withCount('items')
            ->latestWeeks();

        if ($isOwner) {
            if ($requestedOutlet !== null) {
                $historyQuery->forOutlet($requestedOutlet);
            }
        } else {
            $historyQuery->forOutlet((int) $user->outlet_id);
        }

        $history = $historyQuery->limit(8)->get()->map(fn (StockOpname $opname) => [
            'id' => $opname->id,
            'week_start' => $opname->week_start?->toDateString(),
            'week_end' => $opname->week_end?->toDateString(),
            'outlet' => $opname->outlet?->name,
            'outlet_code' => $opname->outlet?->code,
            'user' => $opname->user?->name,
            'items' => (int) $opname->items_count,
            'completed_at' => $opname->completed_at?->toDateTimeString(),
            'is_current_week' => $opname->week_start?->toDateString() === $weekStartDate,
        ])->all();

        $outletStatuses = [];

        if ($isOwner) {
            $outletStatuses = Outlet::ordered()->get()->map(fn (Outlet $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'is_active' => $row->is_active,
                'opname_done' => StockOpname::forOutlet($row->id)->forWeek($weekStartDate)->exists(),
                'last_opname_week' => StockOpname::forOutlet($row->id)
                    ->latestWeeks()
                    ->value('week_start')?->toDateString(),
            ])->all();
        }

        return Inertia::render('StockOpnames/Index', [
            'weekStart' => $weekStartDate,
            'weekEnd' => $weekEnd->toDateString(),
            'outlet' => $outlet?->only(['id', 'name', 'code']),
            'products' => $products,
            'existing' => $existing === null ? null : [
                'id' => $existing->id,
                'completed_at' => $existing->completed_at?->toDateTimeString(),
                'user' => $existing->user?->name,
                'note' => $existing->note,
                'items' => $existing->items->map(fn (StockOpnameItem $item) => [
                    'id' => $item->id,
                    'product' => $item->product?->name,
                    'sku' => $item->product?->sku,
                    'unit' => $item->product?->unit,
                    'system_qty' => (int) $item->system_qty,
                    'counted_qty' => (int) $item->counted_qty,
                    'difference' => (int) $item->difference,
                    'note' => $item->note,
                ])->values(),
            ],
            'history' => $history,
            'outlets' => $isOwner ? Outlet::ordered()->get(['id', 'name', 'code']) : [],
            'outletStatuses' => $outletStatuses,
            'filters' => ['outlet' => $requestedOutlet],
        ]);
    }

    public function store(StoreStockOpnameRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Manajer tidak bisa memilih outlet lain walau mengirim outlet_id.
        $outletId = $user->isOwner()
            ? (int) $validated['outlet_id']
            : (int) $user->outlet_id;

        $this->opname->submit($outletId, $user, $validated['counts'], $validated['note'] ?? null);

        return redirect()
            ->route('stock-opnames.index')
            ->with('success', 'Stock opname tersimpan. Stok sudah disesuaikan dengan hasil hitung.');
    }
}
