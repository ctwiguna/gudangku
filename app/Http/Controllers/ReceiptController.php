<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Daftar penerimaan. Manajer hanya melihat outlet sendiri dan tidak
     * menerima field total. Owner melihat semua outlet.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Purchase::class);

        $user = $request->user();
        $isOwner = $user->isOwner();
        $outletId = $request->integer('outlet') ?: null;

        $query = Purchase::query()
            ->with(['supplier:id,name', 'outlet:id,name,code'])
            ->latestReceived();

        if ($isOwner) {
            if ($outletId !== null) {
                $query->forOutlet($outletId);
            }
        } else {
            $query->forOutlet((int) $user->outlet_id);
        }

        $receipts = $query
            ->withCount('items')
            ->withSum('items as qty', 'quantity')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Purchase $purchase) use ($isOwner): array {
                $row = [
                    'id' => $purchase->id,
                    'received_at' => $purchase->received_at?->toDateString(),
                    'invoice_no' => $purchase->invoice_no,
                    'supplier' => $purchase->supplier?->name,
                    'outlet' => $purchase->outlet?->name,
                    'items' => (int) $purchase->items_count,
                    'qty' => (int) ($purchase->qty ?? 0),
                    'is_priced' => $purchase->isPriced(),
                    'is_paid' => $purchase->isPaid(),
                ];

                if ($isOwner) {
                    $row['total'] = (float) $purchase->total;
                }

                return $row;
            });

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts,
            'canSeePrice' => $isOwner,
            'filters' => ['outlet' => $outletId],
            'outlets' => $isOwner ? Outlet::ordered()->get(['id', 'name', 'code']) : [],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Purchase::class);

        return Inertia::render('Receipts/Form', $this->formProps($request));
    }

    /**
     * Penerimaan barang selalu dicatat tanpa harga: priced_at kosong dan total nol.
     * Stok langsung naik pada tanggal terima, walau belum ada harga dan belum dibayar.
     */
    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $outletId = $user->isOwner()
            ? (int) $validated['outlet_id']
            : (int) $user->outlet_id;

        $receivedAt = Carbon::parse($validated['received_at'], 'Asia/Jakarta')->startOfDay();

        $purchase = DB::transaction(function () use ($validated, $user, $outletId, $receivedAt): Purchase {
            $purchase = Purchase::create([
                'outlet_id' => $outletId,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => $user->id,
                'invoice_no' => $validated['invoice_no'] ?? null,
                'received_at' => $receivedAt->toDateString(),
                'priced_at' => null,
                'paid_at' => null,
                'total' => 0,
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => null,
                    'subtotal' => 0,
                ]);

                $this->stock->record(
                    reference: $purchase,
                    outletId: $outletId,
                    productId: (int) $item['product_id'],
                    userId: $user->id,
                    type: MovementType::In,
                    quantity: (int) $item['quantity'],
                    note: 'Penerimaan barang',
                    movedAt: $receivedAt->copy()->setTime(9, 0),
                );
            }

            return $purchase;
        });

        return redirect()
            ->route('receipts.show', $purchase)
            ->with('success', 'Penerimaan barang tersimpan. Menunggu harga dari owner.');
    }

    public function show(Request $request, Purchase $purchase): Response
    {
        $this->authorize('view', $purchase);

        $isOwner = $request->user()->isOwner();

        $purchase->load([
            'supplier:id,name',
            'outlet:id,name,code',
            'user:id,name',
            'items.product:id,name,sku,unit',
        ]);

        $items = $purchase->items->map(function (PurchaseItem $item) use ($isOwner): array {
            $row = [
                'id' => $item->id,
                'product' => $item->product?->name,
                'sku' => $item->product?->sku,
                'unit' => $item->product?->unit,
                'quantity' => (int) $item->quantity,
            ];

            // Harga item hanya untuk owner.
            if ($isOwner) {
                $row['price'] = $item->price === null ? null : (float) $item->price;
                $row['subtotal'] = (float) $item->subtotal;
            }

            return $row;
        });

        $payload = [
            'id' => $purchase->id,
            'received_at' => $purchase->received_at?->toDateString(),
            'invoice_no' => $purchase->invoice_no,
            'supplier' => $purchase->supplier?->name,
            'outlet' => $purchase->outlet?->name,
            'outlet_code' => $purchase->outlet?->code,
            'created_by' => $purchase->user?->name,
            'note' => $purchase->note,
            'is_priced' => $purchase->isPriced(),
            'is_paid' => $purchase->isPaid(),
            'can_edit' => $request->user()->can('update', $purchase),
            'items' => $items,
        ];

        if ($isOwner) {
            $payload['total'] = (float) $purchase->total;
            $payload['priced_at'] = $purchase->priced_at?->toDateString();
            $payload['paid_at'] = $purchase->paid_at?->toDateString();
        }

        return Inertia::render('Receipts/Show', [
            'receipt' => $payload,
            'canSeePrice' => $isOwner,
        ]);
    }

    public function edit(Request $request, Purchase $purchase): Response
    {
        $this->authorize('update', $purchase);

        $purchase->load('items.product:id,name,sku,unit');

        return Inertia::render('Receipts/Form', [
            ...$this->formProps($request),
            'receipt' => [
                'id' => $purchase->id,
                'outlet_id' => $purchase->outlet_id,
                'supplier_id' => $purchase->supplier_id,
                'received_at' => $purchase->received_at?->toDateString(),
                'invoice_no' => $purchase->invoice_no,
                'note' => $purchase->note,
                'is_priced' => $purchase->isPriced(),
                'items' => $purchase->items->map(fn (PurchaseItem $item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (int) $item->quantity,
                ])->values(),
            ],
        ]);
    }

    /**
     * Mengubah jumlah penerimaan yang masih belum berharga.
     * Setiap selisih menulis movement adjustment, item yang dibuang menulis
     * movement out, dan item baru menulis movement in.
     */
    public function update(UpdateReceiptRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('update', $purchase);

        $user = $request->user();
        $validated = $request->validated();
        $outletId = (int) $purchase->outlet_id;
        $movedAt = $purchase->received_at->copy()->setTime(9, 0);

        DB::transaction(function () use ($purchase, $validated, $user, $outletId, $movedAt): void {
            $submitted = collect($validated['items'])->keyBy('product_id');

            foreach ($purchase->items()->get() as $item) {
                $row = $submitted->get($item->product_id);

                if ($row === null) {
                    // Item dihapus dari form: kembalikan stok sebesar jumlah lama.
                    $this->stock->record(
                        reference: $purchase,
                        outletId: $outletId,
                        productId: (int) $item->product_id,
                        userId: $user->id,
                        type: MovementType::Out,
                        quantity: -1 * (int) $item->quantity,
                        note: 'Pembatalan item penerimaan',
                        movedAt: $movedAt,
                    );

                    $item->delete();

                    continue;
                }

                $difference = (int) $row['quantity'] - (int) $item->quantity;

                if ($difference !== 0) {
                    $this->stock->record(
                        reference: $purchase,
                        outletId: $outletId,
                        productId: (int) $item->product_id,
                        userId: $user->id,
                        type: MovementType::Adjustment,
                        quantity: $difference,
                        note: 'Koreksi jumlah penerimaan',
                        movedAt: $movedAt,
                    );

                    $item->update(['quantity' => (int) $row['quantity']]);
                }

                $submitted->forget($item->product_id);
            }

            foreach ($submitted as $productId => $row) {
                $purchase->items()->create([
                    'product_id' => (int) $productId,
                    'quantity' => (int) $row['quantity'],
                    'price' => null,
                    'subtotal' => 0,
                ]);

                $this->stock->record(
                    reference: $purchase,
                    outletId: $outletId,
                    productId: (int) $productId,
                    userId: $user->id,
                    type: MovementType::In,
                    quantity: (int) $row['quantity'],
                    note: 'Tambahan item penerimaan',
                    movedAt: $movedAt,
                );
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'received_at' => $validated['received_at'],
                'invoice_no' => $validated['invoice_no'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()
            ->route('receipts.show', $purchase)
            ->with('success', 'Perubahan penerimaan tersimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request): array
    {
        $user = $request->user();

        return [
            'receipt' => null,
            'suppliers' => Supplier::ordered()->get(['id', 'name']),
            'products' => Product::active()->ordered()->get(['id', 'name', 'sku', 'unit']),
            'outlets' => $user->isOwner()
                ? Outlet::active()->ordered()->get(['id', 'name', 'code'])
                : [],
            'outletName' => $user->isOwner() ? null : $user->outlet?->name,
            'today' => Carbon::now('Asia/Jakarta')->toDateString(),
        ];
    }
}
