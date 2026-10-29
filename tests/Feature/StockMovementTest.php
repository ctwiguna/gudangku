<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
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

    public function test_manager_only_sees_own_outlet(): void
    {
        $outlet = $this->outletPusat();

        $page = $this->inertiaPage($this->actingAs($this->managerPusat())->get('/stock-movements'));

        $this->assertSame('StockMovements/Index', $page['component']);
        $this->assertEmpty($page['props']['outlets'], 'Manajer tidak menerima daftar semua outlet.');

        $expected = StockMovement::forOutlet($outlet->id)->count();

        $this->assertSame($expected, $page['props']['summary']['rows']);
        $this->assertSame($outlet->name, $page['props']['scopeLabel']);
    }

    public function test_owner_sees_all_outlets(): void
    {
        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/stock-movements'));

        $this->assertSame(StockMovement::count(), $page['props']['summary']['rows']);
        $this->assertNotEmpty($page['props']['outlets']);
        $this->assertSame('Semua outlet', $page['props']['scopeLabel']);
    }

    public function test_type_filter_works(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/stock-movements?type=adjustment'),
        );

        $types = collect($page['props']['movements']['data'])->pluck('type')->unique()->values()->all();

        $this->assertSame(['adjustment'], $types);
        $this->assertSame(StockMovement::where('type', MovementType::Adjustment)->count(), $page['props']['summary']['rows']);
    }

    public function test_invalid_type_filter_is_ignored(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/stock-movements?type=ngawur'),
        );

        $this->assertNull($page['props']['filters']['type']);
        $this->assertSame(StockMovement::count(), $page['props']['summary']['rows']);
    }

    public function test_product_filter_works(): void
    {
        $product = $this->product();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/stock-movements?product={$product->id}"),
        );

        $skus = collect($page['props']['movements']['data'])->pluck('sku')->unique()->values()->all();

        $this->assertSame([$product->sku], $skus);
        $this->assertSame(
            StockMovement::where('product_id', $product->id)->count(),
            $page['props']['summary']['rows'],
        );
    }

    public function test_period_filter_excludes_other_dates(): void
    {
        $outlet = $this->outletPusat();

        StockMovement::create([
            'outlet_id' => $outlet->id,
            'product_id' => $this->product()->id,
            'user_id' => $this->owner()->id,
            'type' => MovementType::In,
            'quantity' => 5,
            'note' => 'Di luar periode',
            'moved_at' => '2020-02-02 08:00:00',
        ]);

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/stock-movements?from=2026-10-01&to=2026-10-31'),
        );

        $notes = collect($page['props']['movements']['data'])->pluck('note')->all();

        $this->assertNotContains('Di luar periode', $notes);

        $expected = StockMovement::whereBetween('moved_at', ['2026-10-01 00:00:00', '2026-10-31 23:59:59'])->count();
        $this->assertSame($expected, $page['props']['summary']['rows']);
    }

    public function test_summary_sums_match_filtered_rows(): void
    {
        $outlet = $this->outletPusat();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/stock-movements?outlet={$outlet->id}"),
        );

        $forOutlet = StockMovement::forOutlet($outlet->id);

        $this->assertSame((int) (clone $forOutlet)->where('type', MovementType::In)->sum('quantity'), $page['props']['summary']['in']);
        $this->assertSame((int) (clone $forOutlet)->where('type', MovementType::Out)->sum('quantity'), $page['props']['summary']['out']);
        $this->assertSame((int) (clone $forOutlet)->where('type', MovementType::Adjustment)->sum('quantity'), $page['props']['summary']['adjustment']);
    }

    /**
     * Bukti audit: saldo produk = jumlah seluruh baris ledger.
     */
    public function test_ledger_balance_matches_product_stock(): void
    {
        $outlet = $this->outletPusat();
        $product = $this->product();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/stock-movements?outlet={$outlet->id}&product={$product->id}"),
        );

        $ledgerSum = collect($page['props']['movements']['data'])->sum('quantity');

        // Halaman dipaginasi, jadi jumlah baris halaman = seluruh baris produk ini per outlet.
        $this->assertSame(StockMovement::forOutlet($outlet->id)->where('product_id', $product->id)->count(), $page['props']['summary']['rows']);
        $this->assertSame($product->stockIn($outlet->id), (int) StockMovement::forOutlet($outlet->id)->where('product_id', $product->id)->sum('quantity'));
        $this->assertSame($product->stockIn($outlet->id), $ledgerSum, 'Seluruh baris ledger produk ini muat dalam satu halaman.');
    }
}
