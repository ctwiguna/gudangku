<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeOutlet(): Outlet
    {
        return Outlet::create(['name' => 'Outlet Uji', 'code' => 'UJI-01']);
    }

    private function makeUser(UserRole $role = UserRole::Manajer, bool $active = true, ?int $outletId = null): User
    {
        $user = new User([
            'name' => 'Pengguna Uji',
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
            'outlet_id' => $outletId,
        ]);

        $user->forceFill(['role' => $role, 'is_active' => $active]);
        $user->save();

        return $user;
    }

    public function test_register_creates_inactive_manager_and_ignores_role_input(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pendaftar Baru',
            'email' => 'pendaftar@gudangku.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // Percobaan menaikkan hak akses lewat form register.
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response->assertRedirect('/login');

        $user = User::where('email', 'pendaftar@gudangku.test')->firstOrFail();

        $this->assertSame(UserRole::Manajer, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertNull($user->outlet_id);
    }

    public function test_register_rejects_duplicate_email_and_short_password(): void
    {
        $this->makeUser();

        $response = $this->post('/register', [
            'name' => 'Ganda',
            'email' => 'uji@gudangku.test',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertSame(1, User::count());
    }

    /**
     * Regresi audit 31 Okt 2026: register publik dibatasi rate limit karena
     * setiap percobaan menjalankan bcrypt dan menulis baris user.
     */
    public function test_register_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/register', [
                'name' => "Pendaftar {$attempt}",
                'email' => "pendaftar{$attempt}@gudangku.test",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertRedirect('/login');
        }

        $this->post('/register', [
            'name' => 'Pendaftar Keenam',
            'email' => 'pendaftar6@gudangku.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->makeUser(UserRole::Manajer, false, $this->makeOutlet()->id);

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_manager_without_outlet_cannot_login(): void
    {
        $this->makeUser(UserRole::Manajer, true, null);

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_active_manager_can_login_and_reaches_dashboard(): void
    {
        $this->makeUser(UserRole::Manajer, true, $this->makeOutlet()->id);

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_owner_can_login_without_outlet(): void
    {
        $this->makeUser(UserRole::Owner, true, null);

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser(UserRole::Owner, true, null);

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $this->makeUser(UserRole::Owner, true, null);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => 'uji@gudangku.test',
                'password' => 'password-salah',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', [
            'email' => 'uji@gudangku.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->makeUser(UserRole::Owner, true, null);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
