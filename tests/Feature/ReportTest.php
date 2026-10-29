<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function manager(): User
    {
        return User::where('email', 'ina@gudangku.test')->firstOrFail();
    }

    private function outlet(): Outlet
    {
        return Outlet::where('code', 'KDM-01')->firstOrFail();
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

    public function test_owner_can_open_reports(): void
    {
        $response = $this->actingAs($this->owner())->get('/reports');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Reports/Index', $page['component']);
        $this->assertArrayHasKey('summary', $page['props']);
        $this->assertArrayHasKey('expensesByCategory', $page['props']);
        $this->assertArrayHasKey('expensesByOutlet', $page['props']);
        $this->assertNotEmpty($page['props']['outlets']);
    }

    public function test_manager_is_forbidden(): void
    {
        $this->actingAs($this->manager())->get('/reports')->assertForbidden();
    }

    public function test_expense_totals_match_database_for_period(): void
    {
        $from = Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString();

        $expected = (float) Expense::between($from, $to)->sum('amount');
        $expectedCount = Expense::between($from, $to)->count();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/reports?from={$from}&to={$to}"),
        );

        $this->assertEqualsWithDelta($expected, $page['props']['summary']['expenses_total'], 0.001);
        $this->assertSame($expectedCount, $page['props']['summary']['expenses_count']);

        // Rincian per kategori harus berjumlah sama dengan total.
        $sumByCategory = collect($page['props']['expensesByCategory'])->sum('total');
        $this->assertEqualsWithDelta($expected, $sumByCategory, 0.001);

        $sumByOutlet = collect($page['props']['expensesByOutlet'])->sum('total');
        $this->assertEqualsWithDelta($expected, $sumByOutlet, 0.001);
    }

    public function test_outlet_filter_scopes_the_report(): void
    {
        $outlet = $this->outlet();
        $from = Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString();

        $expected = (float) Expense::between($from, $to)->forOutlet($outlet->id)->sum('amount');

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/reports?from={$from}&to={$to}&outlet={$outlet->id}"),
        );

        $this->assertEqualsWithDelta($expected, $page['props']['summary']['expenses_total'], 0.001);
        $this->assertSame($outlet->id, $page['props']['filters']['outlet']);
        $this->assertCount(1, $page['props']['expensesByOutlet']);
    }

    public function test_expenses_outside_period_are_excluded(): void
    {
        $outlet = $this->outlet();
        $owner = $this->owner();

        Expense::create([
            'outlet_id' => $outlet->id,
            'user_id' => $owner->id,
            'category' => 'lain',
            'expense_date' => '2020-01-15',
            'amount' => 777000,
            'note' => 'Di luar periode uji',
        ]);

        $page = $this->inertiaPage(
            $this->actingAs($owner)->get('/reports?from=2026-10-01&to=2026-10-31'),
        );

        $notes = collect($page['props']['recentExpenses'])->pluck('note')->all();
        $this->assertNotContains('Di luar periode uji', $notes);

        $expected = (float) Expense::between('2026-10-01', '2026-10-31')->sum('amount');
        $this->assertEqualsWithDelta($expected, $page['props']['summary']['expenses_total'], 0.001);
    }

    public function test_debt_total_matches_unpaid_purchases(): void
    {
        $expected = (float) Purchase::unpaid()->sum('total');

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/reports'));

        $this->assertEqualsWithDelta($expected, $page['props']['summary']['debt_total'], 0.001);
        $this->assertSame(Purchase::unpaid()->count(), $page['props']['summary']['debt_count']);
    }

    public function test_stock_value_matches_ledger_times_price(): void
    {
        $expected = (float) DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->selectRaw('coalesce(sum(m.quantity * p.price_buy), 0) as total')
            ->value('total');

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/reports'));

        $this->assertEqualsWithDelta($expected, $page['props']['summary']['stock_value'], 0.001);
    }

    public function test_invalid_dates_fall_back_to_current_month(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/reports?from=ngawur&to=juga-ngawur'),
        );

        $today = Carbon::now('Asia/Jakarta');

        $this->assertSame($today->copy()->startOfMonth()->toDateString(), $page['props']['filters']['from']);
        $this->assertSame($today->copy()->endOfMonth()->toDateString(), $page['props']['filters']['to']);
    }

    public function test_reversed_period_is_normalized(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/reports?from=2026-10-31&to=2026-10-01'),
        );

        $this->assertSame('2026-10-01', $page['props']['filters']['from']);
        $this->assertSame('2026-10-31', $page['props']['filters']['to']);
    }
}
