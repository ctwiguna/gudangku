<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function managerPusat(): User
    {
        return User::where('email', 'ina@gudangku.test')->firstOrFail();
    }

    private function owner(): User
    {
        return User::where('email', 'owner@gudangku.test')->firstOrFail();
    }

    private function outletPusat(): Outlet
    {
        return Outlet::where('code', 'KDM-01')->firstOrFail();
    }

    private function product(string $sku = 'PLK-KCL'): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function inertiaPage(TestResponse $response): array
    {
        $page = $response->viewData('page');

        if (is_string($page)) {
            return json_decode($page, true) ?? [];
        }

        return json_decode(json_encode($page), true) ?? [];
    }

    public function test_manager_can_record_receipt_without_price(): void
    {
        $product = $this->product();
        $outlet = $this->outletPusat();
        $before = $product->stockIn($outlet->id);

        $this->actingAs($this->managerPusat())
            ->post('/receipts', [
                'supplier_id' => Supplier::firstOrFail()->id,
                'received_at' => '2026-10-27',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ])
            ->assertSessionHasNoErrors();

        $purchase = Purchase::latest('id')->firstOrFail();

        $this->assertNull($purchase->priced_at, 'Penerimaan manajer harus menunggu harga.');
        $this->assertEqualsWithDelta(0, (float) $purchase->total, 0.001);

        $item = $purchase->items()->firstOrFail();
        $this->assertNull($item->price);
        $this->assertEqualsWithDelta(0, (float) $item->subtotal, 0.001);

        // Stok langsung naik sebesar jumlah yang diterima.
        $this->assertSame($before + 10, $product->stockIn($outlet->id));

        $movement = StockMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->where('product_id', $product->id)
            ->get();

        $this->assertCount(1, $movement, 'Satu item menulis tepat satu movement.');
        $this->assertSame(MovementType::In, $movement->first()->type);
        $this->assertSame(10, $movement->first()->quantity);
    }

    /**
     * Klien tidak boleh menyelundupkan harga lewat form penerimaan.
     */
    public function test_client_supplied_price_is_ignored(): void
    {
        $product = $this->product();

        $this->actingAs($this->managerPusat())->post('/receipts', [
            'supplier_id' => Supplier::firstOrFail()->id,
            'received_at' => '2026-10-27',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 999999, 'subtotal' => 1999998],
            ],
        ]);

        $purchase = Purchase::latest('id')->firstOrFail();

        $this->assertNull($purchase->items()->firstOrFail()->price);
        $this->assertEqualsWithDelta(0, (float) $purchase->total, 0.001);
        $this->assertEqualsWithDelta(0, (float) $purchase->items()->firstOrFail()->subtotal, 0.001);
    }

    public function test_manager_receipt_always_uses_own_outlet(): void
    {
        $cabang = Outlet::where('code', 'TMB-01')->firstOrFail();

        $this->actingAs($this->managerPusat())->post('/receipts', [
            'outlet_id' => $cabang->id,
            'supplier_id' => Supplier::firstOrFail()->id,
            'received_at' => '2026-10-27',
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
        ]);

        $purchase = Purchase::latest('id')->firstOrFail();

        $this->assertSame($this->outletPusat()->id, $purchase->outlet_id);
        $this->assertNotSame($cabang->id, $purchase->outlet_id);
    }

    public function test_manager_cannot_view_other_outlet_receipt(): void
    {
        $lain = Purchase::whereHas('outlet', fn ($query) => $query->where('code', 'TMB-01'))
            ->firstOrFail();

        $this->actingAs($this->managerPusat())
            ->get("/receipts/{$lain->id}")
            ->assertForbidden();

        $this->actingAs($this->managerPusat())
            ->get("/receipts/{$lain->id}/edit")
            ->assertForbidden();
    }

    public function test_manager_cannot_edit_receipt_that_is_already_priced(): void
    {
        $priced = Purchase::whereNotNull('priced_at')
            ->whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();

        $this->actingAs($this->managerPusat())
            ->put("/receipts/{$priced->id}", [
                'supplier_id' => $priced->supplier_id,
                'received_at' => $priced->received_at->toDateString(),
                'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
            ])
            ->assertForbidden();
    }

    public function test_manager_can_change_quantity_while_unpriced_and_stock_follows(): void
    {
        $purchase = Purchase::whereNull('priced_at')
            ->whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();

        $item = $purchase->items()->firstOrFail();
        $product = $item->product;
        $outlet = $this->outletPusat();
        $before = $product->stockIn($outlet->id);

        $this->actingAs($this->managerPusat())
            ->put("/receipts/{$purchase->id}", [
                'supplier_id' => $purchase->supplier_id,
                'received_at' => $purchase->received_at->toDateString(),
                'items' => [
                    ['product_id' => $item->product_id, 'quantity' => (int) $item->quantity + 5],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame((int) $item->quantity + 5, (int) $item->fresh()->quantity);
        $this->assertSame($before + 5, $product->stockIn($outlet->id));

        $adjustment = StockMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->where('product_id', $item->product_id)
            ->where('type', MovementType::Adjustment)
            ->firstOrFail();

        $this->assertSame(5, $adjustment->quantity);
    }

    public function test_removing_item_returns_stock(): void
    {
        $purchase = Purchase::whereNull('priced_at')
            ->whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();

        $outlet = $this->outletPusat();
        $items = $purchase->items()->get();

        // Pilih item yang saldonya masih cukup untuk dikembalikan.
        // Setelah opname, sebagian saldo bisa lebih kecil dari jumlah penerimaan.
        $removed = $items->first(
            fn ($item) => $item->product->stockIn($outlet->id) >= (int) $item->quantity,
        );

        $this->assertNotNull($removed, 'Butuh minimal satu item dengan saldo yang cukup.');

        $kept = $items->firstWhere('id', '!=', $removed->id);
        $before = $removed->product->stockIn($outlet->id);

        $this->actingAs($this->managerPusat())
            ->put("/receipts/{$purchase->id}", [
                'supplier_id' => $purchase->supplier_id,
                'received_at' => $purchase->received_at->toDateString(),
                'items' => [
                    ['product_id' => $kept->product_id, 'quantity' => (int) $kept->quantity],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($before - (int) $removed->quantity, $removed->product->stockIn($outlet->id));
        $this->assertDatabaseMissing('purchase_items', ['id' => $removed->id]);
    }

    /**
     * Kalau saldo berjalan lebih kecil dari jumlah penerimaan (misalnya karena
     * selisih opname), penghapusan item ditolak agar saldo tidak negatif.
     */
    public function test_removing_item_is_rejected_when_stock_would_go_negative(): void
    {
        $purchase = Purchase::whereNull('priced_at')
            ->whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();

        $outlet = $this->outletPusat();

        $item = $purchase->items()
            ->get()
            ->first(fn ($row) => $row->product->stockIn($outlet->id) < (int) $row->quantity);

        if ($item === null) {
            $this->markTestSkipped('Data contoh tidak punya item dengan saldo lebih kecil dari jumlah penerimaan.');
        }

        $remaining = $purchase->items()->where('id', '!=', $item->id)->get();

        $this->actingAs($this->managerPusat())
            ->put("/receipts/{$purchase->id}", [
                'supplier_id' => $purchase->supplier_id,
                'received_at' => $purchase->received_at->toDateString(),
                'items' => $remaining->map(fn ($row) => [
                    'product_id' => $row->product_id,
                    'quantity' => (int) $row->quantity,
                ])->values()->all(),
            ])
            ->assertSessionHasErrors('items');

        // Item tetap ada karena transaksi dibatalkan seluruhnya.
        $this->assertDatabaseHas('purchase_items', ['id' => $item->id]);
    }

    /**
     * Penjaga saldo: ledger tidak boleh ditulis kalau saldo jadi negatif.
     */
    public function test_stock_service_rejects_movement_that_makes_balance_negative(): void
    {
        $outlet = $this->outletPusat();
        $product = $this->product('BTL-PRF');
        $purchase = Purchase::whereNull('priced_at')->firstOrFail();

        $this->assertSame(0, $product->stockIn($outlet->id), 'Produk ini belum punya stok di outlet pusat.');

        $this->expectException(ValidationException::class);

        app(StockService::class)->record(
            reference: $purchase,
            outletId: $outlet->id,
            productId: $product->id,
            userId: $this->managerPusat()->id,
            type: MovementType::Out,
            quantity: -1,
            note: 'Uji saldo negatif',
        );
    }

    public function test_receipt_requires_at_least_one_item_and_valid_quantity(): void
    {
        $this->actingAs($this->managerPusat())
            ->post('/receipts', [
                'supplier_id' => Supplier::firstOrFail()->id,
                'received_at' => '2026-10-27',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->actingAs($this->managerPusat())
            ->post('/receipts', [
                'supplier_id' => Supplier::firstOrFail()->id,
                'received_at' => '2026-10-27',
                'items' => [['product_id' => $this->product()->id, 'quantity' => 0]],
            ])
            ->assertSessionHasErrors('items.0.quantity');
    }

    /**
     * Manajer tidak menerima harga pada halaman detail penerimaan.
     */
    public function test_manager_receipt_detail_has_no_price_fields(): void
    {
        $purchase = Purchase::whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();

        $page = $this->inertiaPage(
            $this->actingAs($this->managerPusat())->get("/receipts/{$purchase->id}"),
        );

        $this->assertSame('Receipts/Show', $page['component']);
        $this->assertFalse($page['props']['canSeePrice']);

        $blob = json_encode($page['props']);

        foreach (['price', 'subtotal', 'total', 'priced_at', 'paid_at'] as $key) {
            $this->assertStringNotContainsString('"'.$key.'"', $blob, "Detail manajer tidak boleh memuat {$key}.");
        }
    }

    public function test_owner_receipt_detail_includes_price_fields(): void
    {
        $purchase = Purchase::whereNotNull('priced_at')->firstOrFail();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/receipts/{$purchase->id}"),
        );

        $this->assertTrue($page['props']['canSeePrice']);
        $this->assertArrayHasKey('total', $page['props']['receipt']);
        $this->assertArrayHasKey('price', $page['props']['receipt']['items'][0]);
    }

    public function test_receipt_index_is_scoped_for_manager(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->managerPusat())->get('/receipts'),
        );

        $ids = collect($page['props']['receipts']['data'])->pluck('id')->sort()->values()->all();
        $expected = Purchase::forOutlet($this->outletPusat()->id)->pluck('id')->sort()->values()->all();

        $this->assertSame($expected, $ids);
        $this->assertFalse($page['props']['canSeePrice']);
        $this->assertEmpty($page['props']['outlets']);
    }
}
