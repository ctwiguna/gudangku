<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserTest extends TestCase
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

    private function outlet(): Outlet
    {
        return Outlet::where('code', 'TMB-01')->firstOrFail();
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

    public function test_owner_can_open_user_list_with_summary(): void
    {
        $response = $this->actingAs($this->owner())->get('/users');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Users/Index', $page['component']);
        $this->assertSame(User::count(), $page['props']['summary']['total']);
        $this->assertSame(User::owners()->count(), $page['props']['summary']['owners']);
        $this->assertSame(User::where('is_active', false)->count(), $page['props']['summary']['inactive']);
        $this->assertCount(2, $page['props']['roles']);
    }

    public function test_owner_can_create_manager_with_outlet(): void
    {
        $this->actingAs($this->owner())
            ->post('/users', [
                'name' => 'Manajer Baru Uji',
                'email' => 'manajer.uji@gudangku.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'manajer',
                'outlet_id' => $this->outlet()->id,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'manajer.uji@gudangku.test')->firstOrFail();

        $this->assertSame(UserRole::Manajer, $user->role);
        $this->assertSame($this->outlet()->id, $user->outlet_id);
        $this->assertTrue($user->is_active);
    }

    public function test_manager_role_requires_outlet(): void
    {
        $this->actingAs($this->owner())
            ->post('/users', [
                'name' => 'Manajer Tanpa Outlet',
                'email' => 'tanpa.outlet@gudangku.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'manajer',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('outlet_id');

        $this->assertDatabaseMissing('users', ['email' => 'tanpa.outlet@gudangku.test']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/users', [
                'name' => 'Duplikat',
                'email' => 'owner@gudangku.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'owner',
            ])
            ->assertSessionHasErrors('email');
    }

    /**
     * Owner selalu tanpa outlet, walau form mengirim outlet_id.
     */
    public function test_owner_role_forces_null_outlet(): void
    {
        $this->actingAs($this->owner())
            ->post('/users', [
                'name' => 'Owner Kedua',
                'email' => 'owner2@gudangku.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'owner',
                'outlet_id' => $this->outlet()->id,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(User::where('email', 'owner2@gudangku.test')->firstOrFail()->outlet_id);
    }

    public function test_owner_can_change_role_and_outlet(): void
    {
        $manager = $this->manager();

        $this->actingAs($this->owner())
            ->put("/users/{$manager->id}", [
                'name' => $manager->name,
                'email' => $manager->email,
                'role' => 'owner',
                'outlet_id' => null,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $manager->fresh();

        $this->assertSame(UserRole::Owner, $fresh->role);
        $this->assertNull($fresh->outlet_id, 'Owner tidak terikat outlet.');
    }

    /**
     * Akun hasil register publik (nonaktif, tanpa outlet) bisa dipakai login
     * setelah owner mengaktifkan dan menugaskan outlet.
     */
    public function test_owner_can_activate_pending_account(): void
    {
        $pending = User::where('email', 'kandidat@gudangku.test')->firstOrFail();

        $this->assertFalse($pending->is_active);
        $this->assertNull($pending->outlet_id);

        $this->actingAs($this->owner())
            ->put("/users/{$pending->id}", [
                'name' => $pending->name,
                'email' => $pending->email,
                'role' => 'manajer',
                'outlet_id' => $this->outlet()->id,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($pending->fresh()->is_active);

        // Sekarang akun tersebut bisa login.
        auth()->logout();

        $this->post('/login', [
            'email' => $pending->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($pending->fresh());
    }

    public function test_owner_cannot_deactivate_or_demote_self(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->patch("/users/{$owner->id}/status")
            ->assertSessionHas('error');

        $this->assertTrue($owner->fresh()->is_active);

        $this->actingAs($owner)
            ->put("/users/{$owner->id}", [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'manajer',
                'outlet_id' => $this->outlet()->id,
                'is_active' => true,
            ])
            ->assertSessionHas('error');

        $this->assertSame(UserRole::Owner, $owner->fresh()->role);
    }

    public function test_last_owner_cannot_be_deleted(): void
    {
        $owner = $this->owner();

        $this->assertSame(1, User::owners()->count());

        // Owner kedua dibuat lebih dulu supaya penghapusan diuji lewat akun lain,
        // bukan lewat aturan "tidak bisa menghapus diri sendiri".
        $second = User::where('email', 'ina@gudangku.test')->firstOrFail();

        $this->actingAs($owner)
            ->delete("/users/{$owner->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $owner->id]);

        // Menghapus owner lewat akun owner lain tetap ditolak selama dia owner terakhir.
        $this->actingAs($second)->delete("/users/{$owner->id}")->assertForbidden();
    }

    public function test_user_with_transactions_cannot_be_deleted(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->purchases()->exists());

        $this->actingAs($this->owner())
            ->delete("/users/{$manager->id}")
            ->assertSessionHas('error');

        $this->assertStringContainsString('Nonaktifkan', session('error'));
        $this->assertDatabaseHas('users', ['id' => $manager->id]);
    }

    public function test_user_without_transactions_can_be_deleted(): void
    {
        $pending = User::where('email', 'kandidat@gudangku.test')->firstOrFail();

        $this->actingAs($this->owner())
            ->delete("/users/{$pending->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
    }

    public function test_manager_is_forbidden_on_user_routes(): void
    {
        $manager = $this->manager();
        $target = User::where('email', 'salsi@gudangku.test')->firstOrFail();

        $this->actingAs($manager)->get('/users')->assertForbidden();
        $this->actingAs($manager)->post('/users', [
            'name' => 'Dari Manajer',
            'email' => 'dari.manajer@gudangku.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'owner',
        ])->assertForbidden();
        $this->actingAs($manager)->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'owner',
        ])->assertForbidden();
        $this->actingAs($manager)->patch("/users/{$target->id}/status")->assertForbidden();
        $this->actingAs($manager)->delete("/users/{$target->id}")->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'dari.manajer@gudangku.test']);
        $this->assertSame(UserRole::Manajer, $target->fresh()->role);
    }
}
