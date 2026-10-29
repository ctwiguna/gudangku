<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OutletTest extends TestCase
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

    public function test_owner_can_open_outlet_list(): void
    {
        $response = $this->actingAs($this->owner())->get('/outlets');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Outlets/Index', $page['component']);
        $this->assertCount(Outlet::count(), $page['props']['outlets']);
        $this->assertArrayHasKey('users_count', $page['props']['outlets'][0]);
        $this->assertArrayHasKey('movements_count', $page['props']['outlets'][0]);
    }

    public function test_owner_can_create_outlet(): void
    {
        $this->actingAs($this->owner())
            ->post('/outlets', [
                'name' => 'Outlet Cakrawala Ketiga',
                'code' => 'CKR-03',
                'address' => 'Jl. Baru No. 3',
                'phone' => '021-5550303',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('outlets', ['code' => 'CKR-03', 'is_active' => true]);
    }

    public function test_duplicate_outlet_code_is_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/outlets', ['name' => 'Duplikat', 'code' => 'KDM-01'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Outlet::where('code', 'KDM-01')->count());
    }

    public function test_owner_can_update_outlet(): void
    {
        $outlet = Outlet::where('code', 'TMB-01')->firstOrFail();

        $this->actingAs($this->owner())
            ->put("/outlets/{$outlet->id}", [
                'name' => 'Tembalang Baru',
                'code' => 'TMB-01',
                'address' => 'Alamat baru',
                'phone' => '021-9999999',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('outlets', ['id' => $outlet->id, 'name' => 'Tembalang Baru']);
    }

    public function test_owner_can_toggle_outlet_status(): void
    {
        $outlet = Outlet::where('code', 'TMB-01')->firstOrFail();

        $this->actingAs($this->owner())
            ->patch("/outlets/{$outlet->id}/status")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('outlets', ['id' => $outlet->id, 'is_active' => false]);

        $this->actingAs($this->owner())->patch("/outlets/{$outlet->id}/status");

        $this->assertDatabaseHas('outlets', ['id' => $outlet->id, 'is_active' => true]);
    }

    public function test_outlet_with_data_cannot_be_deleted(): void
    {
        $outlet = Outlet::where('code', 'KDM-01')->firstOrFail();

        $response = $this->actingAs($this->owner())->delete("/outlets/{$outlet->id}");

        $response->assertSessionHas('error');

        $message = session('error');
        $this->assertStringContainsString($outlet->name, $message);
        $this->assertStringContainsString('Nonaktifkan', $message);

        $this->assertDatabaseHas('outlets', ['id' => $outlet->id]);
    }

    public function test_outlet_without_data_can_be_deleted(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Kosong', 'code' => 'KOS-01']);

        $this->actingAs($this->owner())
            ->delete("/outlets/{$outlet->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('outlets', ['id' => $outlet->id]);
    }

    public function test_manager_is_forbidden_on_outlet_routes(): void
    {
        $outlet = Outlet::where('code', 'TMB-01')->firstOrFail();
        $manager = $this->manager();

        $this->actingAs($manager)->get('/outlets')->assertForbidden();
        $this->actingAs($manager)->get('/settings')->assertForbidden();
        $this->actingAs($manager)->post('/outlets', ['name' => 'Dari Manajer', 'code' => 'MGR-99'])->assertForbidden();
        $this->actingAs($manager)->put("/outlets/{$outlet->id}", [
            'name' => 'Diubah manajer',
            'code' => $outlet->code,
        ])->assertForbidden();
        $this->actingAs($manager)->patch("/outlets/{$outlet->id}/status")->assertForbidden();
        $this->actingAs($manager)->delete("/outlets/{$outlet->id}")->assertForbidden();

        $this->assertDatabaseMissing('outlets', ['code' => 'MGR-99']);
        $this->assertDatabaseHas('outlets', ['id' => $outlet->id, 'name' => $outlet->name, 'is_active' => true]);
    }
}
