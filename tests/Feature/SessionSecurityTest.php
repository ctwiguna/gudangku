<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
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
        return User::where('email', 'manajer1@gudangku.test')->firstOrFail();
    }

    private function insertSession(int $userId, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => Carbon::now()->timestamp,
        ]);
    }

    public function test_session_configuration_is_hardened(): void
    {
        $this->assertTrue((bool) config('session.encrypt'), 'Payload sesi harus dienkripsi.');
        $this->assertTrue((bool) config('session.expire_on_close'), 'Sesi harus berakhir saat browser ditutup.');
        $this->assertLessThanOrEqual(60, (int) config('session.lifetime'), 'Idle timeout maksimal 60 menit.');
        $this->assertSame(720, (int) config('session.absolute_lifetime'), 'Absolute timeout harus terpasang.');
        $this->assertTrue((bool) config('session.http_only'), 'Cookie sesi harus HttpOnly.');
        $this->assertSame('lax', config('session.same_site'));
    }

    /**
     * Timestamp login disimpan di server (payload sesi), bukan di client.
     */
    public function test_login_records_server_side_login_timestamp(): void
    {
        $this->post('/login', [
            'email' => 'owner@gudangku.test',
            'password' => 'hidupberkah',
        ]);

        $this->assertAuthenticated();

        $loginAt = session('auth.login_at');

        $this->assertIsInt($loginAt);
        $this->assertEqualsWithDelta(Carbon::now('Asia/Jakarta')->timestamp, $loginAt, 10);
    }

    public function test_absolute_timeout_forces_logout(): void
    {
        $this->actingAs($this->owner());

        // 721 menit sejak login, melewati batas 720 menit.
        session(['auth.login_at' => Carbon::now('Asia/Jakarta')->timestamp - (721 * 60)]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_session_within_absolute_limit_is_kept(): void
    {
        $this->actingAs($this->owner());

        session(['auth.login_at' => Carbon::now('Asia/Jakarta')->timestamp - (60 * 60)]);

        $this->get('/dashboard')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_expired_session_also_blocks_owner_only_routes(): void
    {
        $this->actingAs($this->owner());

        session(['auth.login_at' => Carbon::now('Asia/Jakarta')->timestamp - (900 * 60)]);

        $this->get('/reports')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_changing_own_password_revokes_other_sessions(): void
    {
        $user = $this->manager();
        $this->actingAs($user);

        $this->insertSession((int) $user->id, 'sesi-perangkat-lain');
        $this->insertSession((int) $user->id, 'sesi-perangkat-ketiga');

        $this->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'hidupberkah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasNoErrors();

        // Sesi di perangkat lain dicabut.
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-perangkat-lain']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-perangkat-ketiga']);
    }

    /**
     * Sesi yang sedang dipakai tidak ikut dicabut. Diuji langsung ke service
     * dengan ID tetap, karena ID sesi pada lingkungan test berubah antar-request.
     */
    public function test_session_service_keeps_the_excepted_session(): void
    {
        $user = $this->manager();

        $this->insertSession((int) $user->id, 'sesi-dipakai');
        $this->insertSession((int) $user->id, 'sesi-lain-a');
        $this->insertSession((int) $user->id, 'sesi-lain-b');

        $revoked = app(\App\Services\SessionService::class)
            ->revokeForUser((int) $user->id, 'sesi-dipakai');

        $this->assertSame(2, $revoked);
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-dipakai']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-lain-a']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-lain-b']);
    }

    public function test_owner_resetting_user_password_revokes_that_users_sessions(): void
    {
        $owner = $this->owner();
        $target = $this->manager();

        $this->actingAs($owner);

        $this->insertSession((int) $target->id, 'sesi-target-a');
        $this->insertSession((int) $target->id, 'sesi-target-b');
        $this->insertSession((int) $owner->id, 'sesi-owner-lain');

        $this->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'manajer',
            'outlet_id' => $target->outlet_id,
            'is_active' => true,
            'password' => 'password-diganti-123',
            'password_confirmation' => 'password-diganti-123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target-a']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target-b']);

        // Sesi owner lain tidak boleh ikut tercabut.
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-owner-lain']);
    }

    public function test_deactivating_user_revokes_their_sessions(): void
    {
        $target = $this->manager();

        $this->actingAs($this->owner());
        $this->insertSession((int) $target->id, 'sesi-target-c');

        $this->patch("/users/{$target->id}/status")->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target-c']);
        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_guest_is_not_affected_by_session_freshness_middleware(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
