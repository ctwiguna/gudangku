<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierDebtController extends Controller
{
    /**
     * Detail barang dari supplier yang sudah diterima tetapi belum dibayar.
     *
     * Dikelompokkan per supplier supaya terlihat siapa yang harus dibayar,
     * lengkap dengan daftar barang (produk, jumlah, harga satuan, subtotal)
     * serta total tiap supplier. Hanya owner.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isOwner(), 403, 'Hutang supplier hanya untuk owner.');

        $outletId = $request->integer('outlet') ?: null;

        $purchases = Purchase::query()
            ->unpaid()
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId))
            ->with([
                'supplier:id,name,contact_name,phone,lead_time_days,term_of_payment',
                'outlet:id,name,code',
                'items.product:id,name,sku,unit',
            ])
            ->orderBy('received_at')
            ->get();

        $suppliers = $purchases
            ->groupBy('supplier_id')
            ->map(function ($group) {
                /** @var Purchase $first */
                $first = $group->first();
                $supplier = $first->supplier;

                return [
                    'supplier_id' => $first->supplier_id,
                    'supplier' => $supplier?->name ?? 'Supplier tidak ditemukan',
                    'contact_name' => $supplier?->contact_name,
                    'phone' => $supplier?->phone,
                    'lead_time_days' => $supplier?->lead_time_days,
                    'term_of_payment' => $supplier?->term_of_payment,
                    'purchases_count' => $group->count(),
                    'quantity' => (int) $group->sum(fn (Purchase $purchase) => $purchase->items->sum('quantity')),
                    'total' => (float) $group->sum('total'),
                    'purchases' => $group->map(fn (Purchase $purchase) => [
                        'id' => $purchase->id,
                        'received_at' => $purchase->received_at?->toDateString(),
                        'invoice_no' => $purchase->invoice_no,
                        'outlet' => $purchase->outlet?->name,
                        'outlet_code' => $purchase->outlet?->code,
                        'total' => (float) $purchase->total,
                        'items' => $purchase->items->map(fn ($item) => [
                            'product' => $item->product?->name,
                            'sku' => $item->product?->sku,
                            'unit' => $item->product?->unit,
                            'quantity' => (int) $item->quantity,
                            'price' => $item->price === null ? null : (float) $item->price,
                            'subtotal' => (float) $item->subtotal,
                        ])->values(),
                    ])->values(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return Inertia::render('SupplierDebts/Index', [
            'suppliers' => $suppliers,
            'summary' => [
                'total' => (float) $purchases->sum('total'),
                'purchases' => $purchases->count(),
                'suppliers' => $suppliers->count(),
                'quantity' => (int) $purchases->sum(fn (Purchase $purchase) => $purchase->items->sum('quantity')),
            ],
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'filters' => ['outlet' => $outletId],
        ]);
    }
}
