<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SecurityTest extends TestCase
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
        return User::where('email', 'manajer1@gudangku.test')->firstOrFail();
    }

    private function outletPusat(): Outlet
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

    /**
     * Paginator Laravel membawa key `total` yang artinya jumlah baris,
     * bukan nominal uang. Metadata paginasi dibuang sebelum pemindaian,
     * supaya yang diperiksa hanya isi baris data.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function stripPagination(array $props): array
    {
        foreach ($props as $key => $value) {
            if (is_array($value) && array_key_exists('data', $value) && array_key_exists('current_page', $value)) {
                $props[$key] = ['data' => $value['data']];
            }
        }

        return $props;
    }

    /**
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

    /**
     * CSRF: POST tanpa token harus ditolak 419.
     * Middleware CSRF dilewati saat env testing, jadi env diubah sebentar.
     */
    public function test_request_without_csrf_token_is_rejected(): void
    {
        $this->app['env'] = 'local';

        $response = $this->post('/login', [
            'email' => 'owner@gudangku.test',
            'password' => 'hidupberkah',
        ]);

        $response->assertStatus(419);
        $this->assertGuest();
    }

    /**
     * XSS: catatan berisi tag script disimpan apa adanya dan dikirim sebagai
     * teks. React meng-escape saat render, dan tidak ada dangerouslySetInnerHTML.
     */
    public function test_script_tag_in_note_is_stored_as_plain_text(): void
    {
        $payload = '<script>alert("xss")</script>';

        $this->actingAs($this->owner())->post('/expenses', [
            'outlet_id' => $this->outletPusat()->id,
            'category' => 'lain',
            'expense_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'amount' => 10000,
            'note' => $payload,
        ])->assertSessionHasNoErrors();

        $expense = Expense::where('note', $payload)->firstOrFail();

        $this->assertSame($payload, $expense->note, 'Payload disimpan sebagai teks, bukan dieksekusi.');

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/expenses'));

        $notes = collect($page['props']['expenses']['data'])->pluck('note')->all();
        $this->assertContains($payload, $notes, 'Payload dikirim apa adanya; React yang meng-escape saat render.');
    }

    public function test_frontend_never_uses_dangerously_set_inner_html(): void
    {
        $files = glob(resource_path('js').'/**/*.jsx', GLOB_BRACE)
            + glob(resource_path('js').'/*.jsx');

        $offenders = [];

        foreach ($files as $file) {
            if (str_contains(file_get_contents($file), 'dangerouslySetInnerHTML')) {
                $offenders[] = $file;
            }
        }

        $this->assertSame([], $offenders, 'Tidak boleh ada dangerouslySetInnerHTML di komponen React.');
    }

    /**
     * SQL injection lewat filter: nilai tidak valid jatuh ke bulan berjalan,
     * tidak menghasilkan error database dan tidak meloloskan data.
     */
    public function test_sql_injection_attempt_in_month_filter_is_neutralized(): void
    {
        $response = $this->actingAs($this->owner())
            ->get("/expenses?month=2026-10%27%20OR%20%271%27%3D%271");

        $response->assertOk();

        $page = $this->inertiaPage($response);
        $month = Carbon::now('Asia/Jakarta')->format('Y-m');

        $this->assertSame($month, $page['props']['filters']['month'], 'Filter tidak valid diabaikan.');
        $this->assertEqualsWithDelta(
            (float) Expense::forMonth($month)->sum('amount'),
            $page['props']['summary']['total'],
            0.001,
        );
    }

    public function test_manager_cannot_open_other_outlet_receipt_by_id(): void
    {
        $lain = Purchase::whereHas('outlet', fn ($query) => $query->where('code', 'TMB-01'))
            ->firstOrFail();

        $this->actingAs($this->managerPusat())
            ->get("/receipts/{$lain->id}")
            ->assertForbidden();
    }

    public function test_forbidden_response_renders_inertia_error_page(): void
    {
        $response = $this->actingAs($this->managerPusat())->get('/reports');

        $response->assertForbidden();

        $page = $this->inertiaPage($response);

        $this->assertSame('Errors/403', $page['component']);
        $this->assertSame(403, $page['props']['status']);
    }

    public function test_not_found_response_renders_inertia_error_page(): void
    {
        $response = $this->actingAs($this->owner())->get('/halaman-yang-tidak-ada');

        $response->assertNotFound();

        $page = $this->inertiaPage($response);

        $this->assertSame('Errors/404', $page['component']);
        $this->assertSame(404, $page['props']['status']);
    }

    /**
     * Otorisasi tingkat field: seluruh halaman yang boleh dibuka manajer
     * tidak boleh memuat nama field uang.
     */
    public function test_manager_pages_never_expose_money_keys(): void
    {
        $manager = $this->managerPusat();
        $purchase = Purchase::forOutlet($this->outletPusat()->id)->firstOrFail();

        $routes = [
            '/dashboard',
            '/products',
            '/receipts',
            "/receipts/{$purchase->id}",
            '/stock-opnames',
            '/stock-movements',
            '/profile',
        ];

        $forbidden = [
            'price_buy', 'price', 'subtotal', 'amount', 'total',
            'debt', 'debt_total', 'stock_value', 'expenses_this_month', 'priced_at', 'paid_at',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($manager)->get($route);
            $response->assertOk();

            $keys = $this->collectKeys($this->stripPagination($this->inertiaPage($response)['props']));

            foreach ($forbidden as $key) {
                $this->assertNotContains(
                    $key,
                    $keys,
                    "Props {$route} untuk manajer tidak boleh memuat field {$key}.",
                );
            }
        }
    }

    /**
     * Profil sendiri tidak bisa dipakai menaikkan hak akses.
     */
    public function test_profile_update_cannot_change_role_or_active_status(): void
    {
        $manager = $this->managerPusat();

        $this->actingAs($manager)->put('/profile', [
            'name' => 'Nama Baru Manajer',
            'email' => $manager->email,
            'role' => 'owner',
            'is_active' => true,
            'outlet_id' => null,
        ])->assertSessionHasNoErrors();

        $fresh = $manager->fresh();

        $this->assertSame('Nama Baru Manajer', $fresh->name);
        $this->assertSame(UserRole::Manajer, $fresh->role, 'Role tidak boleh berubah dari halaman profil.');
        $this->assertSame($manager->outlet_id, $fresh->outlet_id);
        $this->assertTrue($fresh->is_active);
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $manager = $this->managerPusat();

        $this->actingAs($manager)->put('/profile', [
            'name' => $manager->name,
            'email' => $manager->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($manager)->put('/profile', [
            'name' => $manager->name,
            'email' => $manager->email,
            'current_password' => 'salah-total',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame(
            $manager->email,
            $manager->fresh()->email,
            'Data profil tidak berubah saat password lama salah.',
        );
    }

    /**
     * Mass assignment: percobaan menaikkan role lewat register tetap manajer.
     */
    public function test_register_cannot_escalate_role(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@gudangku.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'owner',
            'is_active' => true,
        ])->assertRedirect('/login');

        $user = User::where('email', 'penyusup@gudangku.test')->firstOrFail();

        $this->assertSame(UserRole::Manajer, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertNull($user->outlet_id);
    }
}
