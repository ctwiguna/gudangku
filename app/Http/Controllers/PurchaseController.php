<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\StorePurchasePriceRequest;
use App\Models\Outlet;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Antrean pembelian belum berharga dan daftar pembelian yang sudah berharga.
     * Halaman ini owner-only, jadi nilai uang boleh dikirim.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Purchase::class);

        $outletId = $request->integer('outlet') ?: null;

        $base = Purchase::query()
            ->with(['supplier:id,name', 'outlet:id,name,code'])
            ->latestReceived();

        if ($outletId !== null) {
            $base->forOutlet($outletId);
        }

        $queue = (clone $base)
            ->unpriced()
            ->withCount('items')
            ->withSum('items as qty', 'quantity')
            ->get()
            ->map(fn (Purchase $purchase) => $this->row($purchase));

        $purchases = (clone $base)
            ->priced()
            ->withCount('items')
            ->withSum('items as qty', 'quantity')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Purchase $purchase) => $this->row($purchase));

        $month = Carbon::now('Asia/Jakarta');

        return Inertia::render('Purchases/Index', [
            'queue' => $queue,
            'purchases' => $purchases,
            'summary' => [
                'debt_total' => (float) Purchase::unpaid()->sum('total'),
                'debt_count' => Purchase::unpaid()->count(),
                'unpriced_count' => Purchase::unpriced()->count(),
                'paid_this_month' => (float) Purchase::paid()
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('total'),
            ],
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'filters' => ['outlet' => $outletId],
        ]);
    }

    /** Form harga per item. */
    public function edit(Purchase $purchase): Response
    {
        $this->authorize('price', $purchase);

        $purchase->load(['supplier:id,name', 'outlet:id,name,code', 'items.product:id,name,sku,unit']);

        return Inertia::render('Purchases/Price', [
            'purchase' => [
                'id' => $purchase->id,
                'received_at' => $purchase->received_at?->toDateString(),
                'supplier' => $purchase->supplier?->name,
                'outlet' => $purchase->outlet?->name,
                'outlet_code' => $purchase->outlet?->code,
                'invoice_no' => $purchase->invoice_no,
                'note' => $purchase->note,
                'is_priced' => $purchase->isPriced(),
                'is_paid' => $purchase->isPaid(),
                'priced_at' => $purchase->priced_at?->toDateString(),
                'paid_at' => $purchase->paid_at?->toDateString(),
                'total' => (float) $purchase->total,
                'items' => $purchase->items->map(fn (PurchaseItem $item) => [
                    'id' => $item->id,
                    'product' => $item->product?->name,
                    'sku' => $item->product?->sku,
                    'unit' => $item->product?->unit,
                    'quantity' => (int) $item->quantity,
                    'price' => $item->price === null ? '' : (float) $item->price,
                ])->values(),
            ],
        ]);
    }

    /**
     * Menyimpan harga per item, lalu menghitung ulang total di server.
     * priced_at baru diisi kalau seluruh item sudah punya harga.
     */
    public function update(StorePurchasePriceRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('price', $purchase);

        $validated = $request->validated();

        DB::transaction(function () use ($purchase, $validated): void {
            foreach ($validated['items'] as $row) {
                $item = $purchase->items()->whereKey($row['id'])->firstOrFail();
                $price = (float) $row['price'];

                $item->update([
                    'price' => $price,
                    'subtotal' => $price * (int) $item->quantity,
                ]);
            }

            // Total dihitung ulang dari database, bukan dari angka yang dikirim klien.
            $total = (float) $purchase->items()->sum('subtotal');
            $allPriced = $purchase->items()->whereNull('price')->doesntExist();

            $purchase->update([
                'invoice_no' => $validated['invoice_no'] ?? $purchase->invoice_no,
                'note' => $validated['note'] ?? $purchase->note,
                'total' => $total,
                'priced_at' => $allPriced
                    ? ($purchase->priced_at?->toDateString() ?? Carbon::now('Asia/Jakarta')->toDateString())
                    : null,
            ]);
        });

        $message = $purchase->fresh()->isPriced()
            ? 'Harga tersimpan. Pembelian masuk hitungan hutang supplier.'
            : 'Harga tersimpan sebagian. Lengkapi semua item agar masuk hitungan hutang.';

        return redirect()->route('purchases.index')->with('success', $message);
    }

    /** Menandai pembelian sudah dibayar. Hanya boleh setelah dihargai. */
    public function markPaid(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('markPaid', $purchase);

        $validated = $request->validate([
            'paid_at' => ['nullable', 'date'],
        ]);

        $purchase->update([
            'paid_at' => $validated['paid_at'] ?? Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian ditandai sudah dibayar dan keluar dari hitungan hutang.');
    }

    /**
     * Pembelian yang belum berharga dan belum dibayar boleh dihapus owner.
     * Stok dikembalikan lewat movement keluar sebesar jumlah tiap item.
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);

        $label = $purchase->invoice_no ?? 'Penerimaan #'.$purchase->id;
        $movedAt = $purchase->received_at->copy()->setTime(9, 0);

        DB::transaction(function () use ($purchase, $movedAt): void {
            foreach ($purchase->items()->get() as $item) {
                $this->stock->record(
                    reference: $purchase,
                    outletId: (int) $purchase->outlet_id,
                    productId: (int) $item->product_id,
                    userId: (int) auth()->id(),
                    type: MovementType::Out,
                    quantity: -1 * (int) $item->quantity,
                    note: 'Pembatalan penerimaan '.($purchase->invoice_no ?? $purchase->id),
                    movedAt: $movedAt,
                );
            }

            $purchase->delete();
        });

        return redirect()
            ->route('purchases.index')
            ->with('success', "Pembelian {$label} dibatalkan dan stok dikembalikan.");
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'received_at' => $purchase->received_at?->toDateString(),
            'invoice_no' => $purchase->invoice_no,
            'supplier' => $purchase->supplier?->name,
            'outlet' => $purchase->outlet?->name,
            'items' => (int) $purchase->items_count,
            'qty' => (int) ($purchase->qty ?? 0),
            'total' => (float) $purchase->total,
            'is_priced' => $purchase->isPriced(),
            'is_paid' => $purchase->isPaid(),
            'priced_at' => $purchase->priced_at?->toDateString(),
            'paid_at' => $purchase->paid_at?->toDateString(),
        ];
    }
}
