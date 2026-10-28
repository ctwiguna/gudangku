<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    /**
     * Riwayat pergerakan stok dari ledger.
     *
     * Owner melihat semua outlet. Manajer hanya outlet sendiri.
     * Halaman ini adalah bukti audit: saldo stok selalu bisa ditelusuri
     * sampai baris pergerakannya.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        $outletId = $isOwner
            ? ($request->integer('outlet') ?: null)
            : (int) $user->outlet_id;

        $productId = $request->integer('product') ?: null;
        $type = $request->string('type')->toString();
        $type = in_array($type, array_column(MovementType::cases(), 'value'), true) ? $type : null;

        $from = $this->validDate($request->string('from')->toString());
        $to = $this->validDate($request->string('to')->toString());

        $base = StockMovement::query()
            ->with(['product:id,name,sku,unit', 'outlet:id,name,code', 'user:id,name'])
            ->latestFirst();

        if ($outletId !== null) {
            $base->forOutlet($outletId);
        }

        if ($productId !== null) {
            $base->where('product_id', $productId);
        }

        if ($type !== null) {
            $base->ofType(MovementType::from($type));
        }

        if ($from !== null && $to !== null) {
            $base->between($from.' 00:00:00', $to.' 23:59:59');
        } elseif ($from !== null) {
            $base->where('moved_at', '>=', $from.' 00:00:00');
        } elseif ($to !== null) {
            $base->where('moved_at', '<=', $to.' 23:59:59');
        }

        $movements = (clone $base)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'moved_at' => $movement->moved_at?->toDateTimeString(),
                'product' => $movement->product?->name,
                'sku' => $movement->product?->sku,
                'unit' => $movement->product?->unit,
                'outlet' => $movement->outlet?->name,
                'outlet_code' => $movement->outlet?->code,
                'user' => $movement->user?->name,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'quantity' => (int) $movement->quantity,
                'note' => $movement->note,
            ]);

        return Inertia::render('StockMovements/Index', [
            'movements' => $movements,
            'filters' => [
                'outlet' => $isOwner ? $outletId : null,
                'product' => $productId,
                'type' => $type,
                'from' => $from,
                'to' => $to,
            ],
            'outlets' => $isOwner ? Outlet::ordered()->get(['id', 'name', 'code']) : [],
            'products' => Product::ordered()->get(['id', 'name', 'sku']),
            'types' => array_map(
                fn (MovementType $case) => ['value' => $case->value, 'label' => $case->label()],
                MovementType::cases(),
            ),
            'summary' => [
                'in' => (int) (clone $base)->where('type', MovementType::In)->sum('quantity'),
                'out' => (int) (clone $base)->where('type', MovementType::Out)->sum('quantity'),
                'adjustment' => (int) (clone $base)->where('type', MovementType::Adjustment)->sum('quantity'),
                'rows' => (clone $base)->count(),
            ],
            'scopeLabel' => $isOwner
                ? 'Semua outlet'
                : ($user->outlet?->name ?? 'Outlet sendiri'),
        ]);
    }

    private function validDate(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
