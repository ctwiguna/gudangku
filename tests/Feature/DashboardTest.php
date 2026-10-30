<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function manager(): User
    {
        return User::where('email', 'ina@gudangku.test')->firstOrFail();
    }

    /**
     * Mengambil payload halaman Inertia dari view data.
     * Beberapa versi Inertia menyimpan page sebagai string JSON,
     * versi lain sebagai array, jadi keduanya ditangani.
     *
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

    public function test_owner_dashboard_numbers_match_database(): void
    {
        $month = Carbon::now('Asia/Jakarta')->format('Y-m');

        $expectedExpenses = (float) Expense::forMonth($month)->sum('amount');
        $expectedDebt = (float) Purchase::unpaid()->sum('total');
        $expectedUnpriced = Purchase::unpriced()->count();

        $response = $this->actingAs($this->owner())->get('/dashboard');
        $response->assertOk();

        $page = $this->inertiaPage($response);
        $summary = $page['props']['summary'];

        $this->assertSame('Dashboard/Owner', $page['component']);
        $this->assertSame($month, $summary['month']);
        $this->assertEqualsWithDelta($expectedExpenses, $summary['expenses_this_month'], 0.001);
        $this->assertEqualsWithDelta($expectedDebt, $summary['debt_total'], 0.001);
        $this->assertSame($expectedUnpriced, $summary['unpriced_count']);
        $this->assertCount(Outlet::count(), $page['props']['outlets']);
        $this->assertNotEmpty($page['props']['recentReceipts']);
        $this->assertNotEmpty($page['props']['recentExpenses']);
    }

    public function test_stock_value_equals_ledger_times_price(): void
    {
        $expected = (float) DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->selectRaw('coalesce(sum(m.quantity * p.price_buy), 0) as total')
            ->value('total');

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/dashboard'));

        $this->assertEqualsWithDelta($expected, $page['props']['summary']['stock_value'], 0.001);
    }

    public function test_outlet_status_marks_opname_per_outlet(): void
    {
        $pusat = Outlet::where('code', 'KDM-01')->firstOrFail();
        $cabang = Outlet::where('code', 'TMB-01')->firstOrFail();

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/dashboard'));
        $outlets = collect($page['props']['outlets'])->keyBy('code');

        $this->assertTrue($outlets[$pusat->code]['opname_done'], 'Outlet pusat sudah opname minggu ini.');
        $this->assertFalse($outlets[$cabang->code]['opname_done'], 'Outlet cabang belum opname minggu ini.');
    }

    public function test_below_min_products_are_listed_for_owner(): void
    {
        $product = Product::where('sku', 'BTL-PRF')->firstOrFail();

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/dashboard'));
        $rows = collect($page['props']['belowMin']);

        $this->assertNotEmpty($rows, 'Daftar produk di bawah minimum tidak boleh kosong.');
        $this->assertTrue(
            $rows->contains(fn (array $row) => $row['sku'] === $product->sku && $row['stock'] < $row['min_stock']),
            'Produk dengan stok di bawah minimum harus muncul di daftar.',
        );
    }

    /**
     * Otorisasi tingkat field: manajer menerima data stok outlet sendiri,
     * tetapi tidak menerima satu pun nilai uang.
     */
    public function test_manager_dashboard_props_contain_no_money_fields(): void
    {
        $response = $this->actingAs($this->manager())->get('/dashboard');
        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Dashboard/Manager', $page['component']);

        // Props agregat milik owner tidak boleh ikut terkirim.
        foreach (['outlets', 'recentExpenses'] as $key) {
            $this->assertArrayNotHasKey($key, $page['props'], "Props manajer tidak boleh memuat {$key}.");
        }

        // Diperiksa per nama key, bukan substring: key `is_priced` adalah
        // penanda status boolean, bukan nilai harga.
        $keys = $this->collectKeys($page['props']);

        foreach (
            ['price', 'price_buy', 'subtotal', 'amount', 'total', 'debt', 'debt_total', 'stock_value', 'expenses_this_month'] as $key
        ) {
            $this->assertNotContains($key, $keys, "Props manajer tidak boleh memuat key {$key}.");
        }
    }

    /**
     * Kumpulkan seluruh nama key di dalam props secara rekursif.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<int, string>
     */
    private function collectKeys(array $data): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $keys[] = (string) $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->collectKeys($value));
            }
        }

        return $keys;
    }

    public function test_manager_opname_reminder_follows_outlet_compliance(): void
    {
        // Outlet pusat sudah opname minggu ini, jadi tidak ada pengingat.
        $pusatPage = $this->inertiaPage($this->actingAs($this->manager())->get('/dashboard'));
        $this->assertFalse($pusatPage['props']['summary']['opname_due']);

        // Outlet cabang belum opname, jadi pengingat aktif.
        $cabang = User::where('email', 'salsi@gudangku.test')->firstOrFail();
        $cabangPage = $this->inertiaPage($this->actingAs($cabang)->get('/dashboard'));
        $this->assertTrue($cabangPage['props']['summary']['opname_due']);
    }

    public function test_manager_dashboard_is_scoped_to_own_outlet(): void
    {
        $cabang = User::where('email', 'salsi@gudangku.test')->firstOrFail();

        $page = $this->inertiaPage($this->actingAs($cabang)->get('/dashboard'));

        $expected = Purchase::forOutlet((int) $cabang->outlet_id)->pluck('id')->sort()->values()->all();
        $actual = collect($page['props']['recentReceipts'])->pluck('id')->sort()->values()->all();

        $this->assertSame($expected, $actual, 'Manajer hanya boleh menerima pembelian outlet sendiri.');
        $this->assertNotEmpty($actual);

        // Produk di bawah minimum juga hanya dari outlet sendiri.
        $ownOutletProducts = Product::active()
            ->belowMinStock((int) $cabang->outlet_id)
            ->pluck('sku')
            ->sort()
            ->values()
            ->all();
        $listed = collect($page['props']['belowMin'])->pluck('sku')->sort()->values()->all();

        $this->assertSame($ownOutletProducts, $listed);
    }
}
