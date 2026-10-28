<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExpenseTest extends TestCase
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
        return User::where('email', 'manajer.pusat@gudangku.test')->firstOrFail();
    }

    private function outlet(): Outlet
    {
        return Outlet::where('code', 'CKR-01')->firstOrFail();
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

    public function test_owner_can_open_expense_list(): void
    {
        $response = $this->actingAs($this->owner())->get('/expenses');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Expenses/Index', $page['component']);
        $this->assertArrayHasKey('expenses', $page['props']);
        $this->assertArrayHasKey('summary', $page['props']);
        $this->assertArrayHasKey('outlets', $page['props']);
        $this->assertArrayHasKey('categories', $page['props']);
    }

    public function test_owner_can_record_expense(): void
    {
        $response = $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => $this->outlet()->id,
            'category' => 'listrik',
            'expense_date' => '2026-10-20',
            'amount' => 250000,
            'note' => 'Token listrik uji',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'outlet_id' => $this->outlet()->id,
            'category' => 'listrik',
            'amount' => 250000,
            'note' => 'Token listrik uji',
        ]);
    }

    public function test_gaji_requires_week_period(): void
    {
        $before = Expense::count();

        $response = $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => $this->outlet()->id,
            'category' => 'gaji',
            'expense_date' => '2026-10-20',
            'amount' => 1500000,
        ]);

        $response->assertSessionHasErrors(['period_start', 'period_end']);

        // Tidak ada baris baru: perbandingan jumlah lebih aman daripada
        // mencocokkan nominal, karena seeder memang sudah punya gaji 1.500.000.
        $this->assertSame($before, Expense::count());
    }

    public function test_gaji_is_saved_with_period(): void
    {
        $response = $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => $this->outlet()->id,
            'category' => 'gaji',
            'expense_date' => '2026-10-25',
            'period_start' => '2026-10-19',
            'period_end' => '2026-10-25',
            'amount' => 1500000,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'category' => 'gaji',
            'period_start' => '2026-10-19 00:00:00',
            'period_end' => '2026-10-25 00:00:00',
        ]);
    }

    public function test_non_salary_category_does_not_require_period(): void
    {
        $response = $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => $this->outlet()->id,
            'category' => 'sewa',
            'expense_date' => '2026-10-01',
            'amount' => 3000000,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expenses', ['category' => 'sewa', 'amount' => 3000000]);
    }

    public function test_amount_must_be_positive_and_outlet_must_exist(): void
    {
        $response = $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => 99999,
            'category' => 'lain',
            'expense_date' => '2026-10-10',
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors(['outlet_id', 'amount']);
    }

    public function test_owner_can_update_and_delete_expense(): void
    {
        $expense = Expense::where('category', 'lain')->firstOrFail();

        $this->actingAs($this->owner())
            ->put("/expenses/{$expense->id}", [
                'outlet_id' => $expense->outlet_id,
                'category' => 'lain',
                'expense_date' => $expense->expense_date->toDateString(),
                'amount' => 175000,
                'note' => 'Sudah dikoreksi',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 175000, 'note' => 'Sudah dikoreksi']);

        $this->actingAs($this->owner())
            ->delete("/expenses/{$expense->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_month_filter_only_returns_selected_month(): void
    {
        $month = Carbon::now('Asia/Jakarta')->format('Y-m');
        $previous = Carbon::now('Asia/Jakarta')->subMonth()->format('Y-m');

        Expense::create([
            'outlet_id' => $this->outlet()->id,
            'user_id' => $this->owner()->id,
            'category' => 'lain',
            'expense_date' => $previous.'-15',
            'amount' => 111000,
            'note' => 'Bulan sebelumnya',
        ]);

        $page = $this->inertiaPage($this->actingAs($this->owner())->get("/expenses?month={$month}"));

        $this->assertSame($month, $page['props']['filters']['month']);

        $notes = collect($page['props']['expenses']['data'])->pluck('note')->all();

        $this->assertNotContains('Bulan sebelumnya', $notes);

        $previousPage = $this->inertiaPage($this->actingAs($this->owner())->get("/expenses?month={$previous}"));
        $previousNotes = collect($previousPage['props']['expenses']['data'])->pluck('note')->all();

        $this->assertContains('Bulan sebelumnya', $previousNotes);
    }

    /**
     * Manajer tidak boleh menyentuh data keuangan sama sekali.
     */
    public function test_manager_is_forbidden_on_all_expense_routes(): void
    {
        $manager = $this->manager();
        $expense = Expense::firstOrFail();

        $this->actingAs($manager)->get('/expenses')->assertForbidden();
        $this->actingAs($manager)->get('/expenses/create')->assertForbidden();
        $this->actingAs($manager)->get("/expenses/{$expense->id}/edit")->assertForbidden();

        $this->actingAs($manager)->post('/expenses', [
            'outlet_id' => $expense->outlet_id,
            'category' => 'lain',
            'expense_date' => '2026-10-10',
            'amount' => 50000,
        ])->assertForbidden();

        $this->actingAs($manager)->put("/expenses/{$expense->id}", [
            'outlet_id' => $expense->outlet_id,
            'category' => 'lain',
            'expense_date' => '2026-10-10',
            'amount' => 50000,
        ])->assertForbidden();

        $this->actingAs($manager)->delete("/expenses/{$expense->id}")->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/expenses')->assertRedirect('/login');
    }
}
