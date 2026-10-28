<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StockOpnameTest extends TestCase
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
        return User::where('email', 'manajer.pusat@gudangku.test')->firstOrFail();
    }

    /** Outlet cabang belum punya opname minggu ini. */
    private function managerCabang(): User
    {
        return User::where('email', 'manajer.cabang@gudangku.test')->firstOrFail();
    }

    private function outletCabang(): Outlet
    {
        return Outlet::where('code', 'CKR-02')->firstOrFail();
    }

    /**
     * Susun payload counts untuk semua produk aktif, dengan penyesuaian
     * jumlah hitung pada beberapa SKU.
     *
     * @param  array<string, int>  $overrides
     * @return array<int, array{product_id: int, counted_qty: int}>
     */
    private function counts(Outlet $outlet, array $overrides = []): array
    {
        return Product::active()->ordered()->get()->map(function (Product $product) use ($outlet, $overrides) {
            $counted = $overrides[$product->sku] ?? $product->stockIn($outlet->id);

            return ['product_id' => $product->id, 'counted_qty' => (int) $counted];
        })->all();
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

    public function test_manager_can_submit_opname_and_stock_equals_counted(): void
    {
        $cabang = $this->outletCabang();
        $manager = $this->managerCabang();

        // BTL-PRF stok sistem 30, dihitung fisik 28. BOX-SFT 12 tetap 12.
        $counts = $this->counts($cabang, ['BTL-PRF' => 28, 'BOX-SFT' => 12]);

        $this->actingAs($manager)
            ->post('/stock-opnames', ['counts' => $counts, 'note' => 'Opname uji'])
            ->assertSessionHasNoErrors();

        $opname = StockOpname::where('outlet_id', $cabang->id)->latest('id')->firstOrFail();

        $this->assertNotNull($opname->completed_at, 'completed_at harus terisi setelah submit.');
        $this->assertSame(StockOpname::currentWeekStart(), $opname->week_start->toDateString());
        $this->assertCount(Product::active()->count(), $opname->items);

        // Stok setiap produk harus sama dengan jumlah hitung.
        foreach ($opname->items as $item) {
            $this->assertSame(
                $item->counted_qty,
                $item->product->stockIn($cabang->id),
                "Stok {$item->product->sku} harus sama dengan jumlah hitung.",
            );
        }

        $this->assertSame(28, Product::where('sku', 'BTL-PRF')->firstOrFail()->stockIn($cabang->id));
    }

    /**
     * Stok sistem dihitung ulang di server, bukan memakai angka dari form.
     */
    public function test_system_quantity_is_recalculated_on_server(): void
    {
        $cabang = $this->outletCabang();

        $counts = $this->counts($cabang, ['BTL-PRF' => 25]);

        // Klien mencoba mengirim system_qty palsu pada baris pertama.
        $counts[0]['system_qty'] = 9999;
        $counts[0]['difference'] = -9999;

        $this->actingAs($this->managerCabang())
            ->post('/stock-opnames', ['counts' => $counts])
            ->assertSessionHasNoErrors();

        $opname = StockOpname::where('outlet_id', $cabang->id)->latest('id')->firstOrFail();

        foreach ($opname->items as $item) {
            $this->assertNotSame(9999, $item->system_qty);
            $this->assertSame($item->counted_qty - $item->system_qty, $item->difference);
        }
    }

    public function test_only_non_zero_differences_write_adjustment_movements(): void
    {
        $cabang = $this->outletCabang();

        $counts = $this->counts($cabang, ['BTL-PRF' => 27, 'BOX-SFT' => 15]);

        $this->actingAs($this->managerCabang())
            ->post('/stock-opnames', ['counts' => $counts])
            ->assertSessionHasNoErrors();

        $opname = StockOpname::where('outlet_id', $cabang->id)->latest('id')->firstOrFail();

        $movements = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opname->id)
            ->get();

        $this->assertCount(2, $movements, 'Hanya dua produk yang selisihnya tidak nol.');
        $this->assertTrue($movements->every(fn ($movement) => $movement->type === MovementType::Adjustment));

        $byProduct = $movements->keyBy('product_id');

        $btl = Product::where('sku', 'BTL-PRF')->firstOrFail();
        $box = Product::where('sku', 'BOX-SFT')->firstOrFail();

        $this->assertSame(-3, $byProduct[$btl->id]->quantity);
        $this->assertSame(3, $byProduct[$box->id]->quantity);

        // Selisih nol tidak menulis movement.
        $zeroDiff = $opname->items()->where('difference', 0)->count();
        $this->assertGreaterThan(0, $zeroDiff);
        $this->assertSame(
            $opname->items()->where('difference', '!=', 0)->count(),
            $movements->count(),
        );
    }

    public function test_second_opname_in_same_week_is_rejected(): void
    {
        // Outlet pusat sudah opname minggu ini lewat seeder.
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();
        $before = StockOpname::count();

        $response = $this->actingAs($this->managerPusat())
            ->post('/stock-opnames', ['counts' => $this->counts($pusat)]);

        $response->assertSessionHasErrors('counts');
        $this->assertStringContainsString('satu opname per minggu', session('errors')->first('counts'));

        $this->assertSame($before, StockOpname::count(), 'Opname ganda tidak boleh tersimpan.');
    }

    public function test_manager_cannot_target_another_outlet(): void
    {
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();

        $this->actingAs($this->managerCabang())->post('/stock-opnames', [
            'outlet_id' => $pusat->id,
            'counts' => $this->counts($this->outletCabang()),
        ]);

        $created = StockOpname::latest('id')->firstOrFail();

        $this->assertSame($this->outletCabang()->id, $created->outlet_id);
        $this->assertNotSame($pusat->id, $created->outlet_id);
    }

    public function test_owner_can_submit_opname_for_any_outlet(): void
    {
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();

        // Owner mengulang opname minggu ini di outlet pusat: tetap ditolak
        // karena aturan satu opname per minggu berlaku untuk semua role.
        $this->actingAs($this->owner())
            ->post('/stock-opnames', [
                'outlet_id' => $pusat->id,
                'counts' => $this->counts($pusat),
            ])
            ->assertSessionHasErrors('counts');

        // Untuk outlet yang belum opname, owner boleh menyimpan.
        $cabang = $this->outletCabang();

        $this->actingAs($this->owner())
            ->post('/stock-opnames', [
                'outlet_id' => $cabang->id,
                'counts' => $this->counts($cabang, ['BTL-PRF' => 29]),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(29, Product::where('sku', 'BTL-PRF')->firstOrFail()->stockIn($cabang->id));
    }

    public function test_reminder_disappears_after_opname(): void
    {
        $manager = $this->managerCabang();

        $before = $this->inertiaPage($this->actingAs($manager)->get('/dashboard'));
        $this->assertTrue($before['props']['summary']['opname_due'], 'Cabang belum opname, pengingat aktif.');

        $this->actingAs($manager)->post('/stock-opnames', [
            'counts' => $this->counts($this->outletCabang()),
        ]);

        $after = $this->inertiaPage($this->actingAs($manager)->get('/dashboard'));
        $this->assertFalse($after['props']['summary']['opname_due'], 'Setelah opname, pengingat harus hilang.');
    }

    public function test_opname_page_shows_form_for_outlet_without_opname(): void
    {
        $page = $this->inertiaPage($this->actingAs($this->managerCabang())->get('/stock-opnames'));

        $this->assertSame('StockOpnames/Index', $page['component']);
        $this->assertNull($page['props']['existing']);
        $this->assertCount(Product::active()->count(), $page['props']['products']);
        $this->assertArrayHasKey('system_qty', $page['props']['products'][0]);
    }

    public function test_opname_page_shows_result_for_outlet_that_already_did_it(): void
    {
        $page = $this->inertiaPage($this->actingAs($this->managerPusat())->get('/stock-opnames'));

        $this->assertNotNull($page['props']['existing']);
        $this->assertEmpty($page['props']['products'], 'Form tidak ditampilkan kalau sudah opname.');

        $item = $page['props']['existing']['items'][0];

        $this->assertArrayHasKey('system_qty', $item);
        $this->assertArrayHasKey('counted_qty', $item);
        $this->assertArrayHasKey('difference', $item);
    }

    public function test_history_is_scoped_for_manager(): void
    {
        // Buat riwayat di cabang juga, supaya scoping benar-benar teruji
        // dengan dua outlet yang sama-sama punya opname.
        $this->actingAs($this->managerCabang())
            ->post('/stock-opnames', ['counts' => $this->counts($this->outletCabang())])
            ->assertSessionHasNoErrors();

        $pusatPage = $this->inertiaPage($this->actingAs($this->managerPusat())->get('/stock-opnames'));
        $pusatCodes = collect($pusatPage['props']['history'])->pluck('outlet_code')->unique()->values()->all();

        $this->assertSame(['CKR-01'], $pusatCodes, 'Manajer pusat hanya melihat riwayat outlet pusat.');
        $this->assertEmpty($pusatPage['props']['outlets']);
        $this->assertEmpty($pusatPage['props']['outletStatuses']);

        $cabangPage = $this->inertiaPage($this->actingAs($this->managerCabang())->get('/stock-opnames'));
        $cabangCodes = collect($cabangPage['props']['history'])->pluck('outlet_code')->unique()->values()->all();

        $this->assertSame(['CKR-02'], $cabangCodes, 'Manajer cabang hanya melihat riwayat outlet cabang.');
    }

    public function test_all_active_products_must_be_counted(): void
    {
        $cabang = $this->outletCabang();
        $counts = $this->counts($cabang);

        array_pop($counts);

        $this->actingAs($this->managerCabang())
            ->post('/stock-opnames', ['counts' => $counts])
            ->assertSessionHasErrors('counts');

        $this->assertSame(0, StockOpname::where('outlet_id', $cabang->id)->count());
    }

    public function test_opname_items_snapshot_system_quantity(): void
    {
        $cabang = $this->outletCabang();
        $product = Product::where('sku', 'BTL-PRF')->firstOrFail();

        $this->actingAs($this->managerCabang())->post('/stock-opnames', [
            'counts' => $this->counts($cabang, ['BTL-PRF' => 20]),
        ]);

        $opname = StockOpname::where('outlet_id', $cabang->id)->latest('id')->firstOrFail();

        $item = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame(30, $item->system_qty, 'Stok sistem sebelum penyesuaian harus tersimpan.');
        $this->assertSame(20, $item->counted_qty);
        $this->assertSame(-10, $item->difference);
        $this->assertSame(20, $product->stockIn($cabang->id));
    }
}
