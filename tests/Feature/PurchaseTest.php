<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DemoDataSeeder::class);
    }

    private function owner(): User
    {
        return User::where('email', 'owner@gudangku.test')->firstOrFail();
    }

    private function managerPusat(): User
    {
        return User::where('email', 'ina@gudangku.test')->firstOrFail();
    }

    private function outletPusat(): Outlet
    {
        return Outlet::where('code', 'KDM-01')->firstOrFail();
    }

    /** Pembelian outlet pusat yang belum dihargai. */
    private function unpriced(): Purchase
    {
        return Purchase::whereNull('priced_at')
            ->whereHas('outlet', fn ($query) => $query->where('code', 'KDM-01'))
            ->firstOrFail();
    }

    /**
     * @return array<int, array{id: int, price: int}>
     */
    private function priceRows(Purchase $purchase, int $price = 1000): array
    {
        return $purchase->items()->pluck('id')
            ->map(fn ($id) => ['id' => (int) $id, 'price' => $price])
            ->all();
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

    public function test_owner_can_fill_all_prices_and_purchase_becomes_priced(): void
    {
        $purchase = $this->unpriced();

        $prices = $purchase->items()
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => 1500]);

        $expectedTotal = $purchase->items->sum(fn ($item) => 1500 * (int) $item->quantity);

        $rows = $prices->map(fn ($price, $id) => ['id' => (int) $id, 'price' => $price])->values()->all();

        $this->actingAs($this->owner())
            ->put("/purchases/{$purchase->id}/price", ['items' => $rows])
            ->assertSessionHasNoErrors();

        $fresh = $purchase->fresh();

        $this->assertNotNull($fresh->priced_at, 'Semua item berharga, jadi priced_at harus terisi.');
        $this->assertEqualsWithDelta($expectedTotal, (float) $fresh->total, 0.001);
        $this->assertTrue($fresh->isDebt(), 'Pembelian berharga yang belum dibayar masuk hitungan hutang.');

        foreach ($fresh->items as $item) {
            $this->assertEqualsWithDelta(1500, (float) $item->price, 0.001);
            $this->assertEqualsWithDelta(1500 * (int) $item->quantity, (float) $item->subtotal, 0.001);
        }
    }

    /**
     * Harga tidak boleh diisi sebagian lalu pembelian dianggap selesai.
     */
    public function test_partial_prices_are_rejected(): void
    {
        $purchase = $this->unpriced();
        $firstItem = $purchase->items()->firstOrFail();

        $response = $this->actingAs($this->owner())
            ->put("/purchases/{$purchase->id}/price", [
                'items' => [['id' => $firstItem->id, 'price' => 5000]],
            ]);

        $response->assertSessionHasErrors('items');

        $fresh = $purchase->fresh();

        $this->assertNull($fresh->priced_at);
        $this->assertEqualsWithDelta(0, (float) $fresh->total, 0.001);
        $this->assertNull($fresh->items()->whereKey($firstItem->id)->firstOrFail()->price);
    }

    public function test_item_from_another_purchase_is_rejected(): void
    {
        $purchase = $this->unpriced();
        $foreignItem = Purchase::where('id', '!=', $purchase->id)
            ->whereHas('items')
            ->firstOrFail()
            ->items()
            ->firstOrFail();

        $rows = $this->priceRows($purchase);
        $rows[0]['id'] = $foreignItem->id;

        $this->actingAs($this->owner())
            ->put("/purchases/{$purchase->id}/price", ['items' => $rows])
            ->assertSessionHasErrors('items');

        $this->assertNull($purchase->fresh()->priced_at);
    }

    public function test_client_supplied_total_is_ignored(): void
    {
        $purchase = $this->unpriced();
        $rows = $this->priceRows($purchase, 2000);

        $expected = $purchase->items->sum(fn ($item) => 2000 * (int) $item->quantity);

        $this->actingAs($this->owner())
            ->put("/purchases/{$purchase->id}/price", [
                'items' => $rows,
                'total' => 1,
                'priced_at' => '2000-01-01',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $purchase->fresh();

        $this->assertEqualsWithDelta($expected, (float) $fresh->total, 0.001);
        $this->assertNotSame('2000-01-01', $fresh->priced_at?->toDateString());
    }

    public function test_purchase_must_be_priced_before_marked_paid(): void
    {
        $purchase = $this->unpriced();

        $this->actingAs($this->owner())
            ->patch("/purchases/{$purchase->id}/paid")
            ->assertForbidden();

        $this->assertNull($purchase->fresh()->paid_at);
    }

    public function test_owner_can_mark_paid_and_debt_drops(): void
    {
        $purchase = $this->unpriced();

        $debtBefore = (float) Purchase::unpaid()->sum('total');

        $this->actingAs($this->owner())
            ->put("/purchases/{$purchase->id}/price", ['items' => $this->priceRows($purchase, 1000)])
            ->assertSessionHasNoErrors();

        $priced = $purchase->fresh();
        $total = (float) $priced->total;

        $this->assertEqualsWithDelta($debtBefore + $total, (float) Purchase::unpaid()->sum('total'), 0.001);

        $this->actingAs($this->owner())
            ->patch("/purchases/{$priced->id}/paid", ['paid_at' => '2026-10-28'])
            ->assertSessionHas('success');

        $paid = $priced->fresh();

        $this->assertNotNull($paid->paid_at);
        $this->assertFalse($paid->isDebt());
        $this->assertEqualsWithDelta($debtBefore, (float) Purchase::unpaid()->sum('total'), 0.001);
    }

    public function test_owner_can_delete_unpriced_purchase_and_stock_returns(): void
    {
        $outlet = $this->outletPusat();
        $product = Product::where('sku', 'SLT-01')->firstOrFail();

        // Manajer menerima 2 unit, lalu owner membatalkannya.
        $this->actingAs($this->managerPusat())->post('/receipts', [
            'supplier_id' => Supplier::firstOrFail()->id,
            'received_at' => '2026-10-28',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $purchase = Purchase::latest('id')->firstOrFail();
        $afterReceipt = $product->stockIn($outlet->id);

        $this->actingAs($this->owner())
            ->delete("/purchases/{$purchase->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
        $this->assertSame($afterReceipt - 2, $product->stockIn($outlet->id));

        $out = StockMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->where('type', MovementType::Out)
            ->firstOrFail();

        $this->assertSame(-2, $out->quantity);
    }

    /**
     * Pembatalan ditolak kalau stok berjalan lebih kecil dari jumlah item,
     * misalnya karena selisih opname. Saldo tidak boleh jadi negatif.
     */
    public function test_delete_is_rejected_when_stock_is_insufficient(): void
    {
        $purchase = $this->unpriced();
        $outlet = $this->outletPusat();

        $blocked = $purchase->items->contains(
            fn ($item) => $item->product->stockIn($outlet->id) < (int) $item->quantity,
        );

        if (! $blocked) {
            $this->markTestSkipped('Data contoh tidak punya item dengan saldo lebih kecil dari jumlah penerimaan.');
        }

        $this->actingAs($this->owner())
            ->delete("/purchases/{$purchase->id}")
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_owner_cannot_delete_priced_purchase(): void
    {
        $priced = Purchase::whereNotNull('priced_at')->firstOrFail();

        $this->actingAs($this->owner())
            ->delete("/purchases/{$priced->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('purchases', ['id' => $priced->id]);
    }

    public function test_manager_is_forbidden_on_purchase_routes(): void
    {
        $purchase = $this->unpriced();
        $manager = $this->managerPusat();

        $this->actingAs($manager)->get('/purchases')->assertForbidden();
        $this->actingAs($manager)->get("/purchases/{$purchase->id}/price")->assertForbidden();
        $this->actingAs($manager)
            ->put("/purchases/{$purchase->id}/price", ['items' => $this->priceRows($purchase)])
            ->assertForbidden();
        $this->actingAs($manager)->patch("/purchases/{$purchase->id}/paid")->assertForbidden();
        $this->actingAs($manager)->delete("/purchases/{$purchase->id}")->assertForbidden();

        $this->assertNull($purchase->fresh()->priced_at);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_index_shows_queue_summary_and_scoped_filter(): void
    {
        $pusat = $this->outletPusat();

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/purchases'));

        $this->assertSame('Purchases/Index', $page['component']);

        $queueIds = collect($page['props']['queue'])->pluck('id')->sort()->values()->all();
        $expectedQueue = Purchase::unpriced()->pluck('id')->sort()->values()->all();

        $this->assertSame($expectedQueue, $queueIds);
        $this->assertSame(Purchase::unpriced()->count(), $page['props']['summary']['unpriced_count']);
        $this->assertEqualsWithDelta(
            (float) Purchase::unpaid()->sum('total'),
            $page['props']['summary']['debt_total'],
            0.001,
        );

        $filtered = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/purchases?outlet={$pusat->id}"),
        );

        $filteredIds = collect($filtered['props']['purchases']['data'])->pluck('id')->all();
        $expectedFiltered = Purchase::priced()->forOutlet($pusat->id)->pluck('id')->all();

        $this->assertEqualsCanonicalizing($expectedFiltered, $filteredIds);
    }
}
