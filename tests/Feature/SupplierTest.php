<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SupplierTest extends TestCase
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
     * Database supplier memuat informasi lengkap pemasok.
     */
    public function test_owner_can_open_supplier_database(): void
    {
        $response = $this->actingAs($this->owner())->get('/suppliers');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Suppliers/Index', $page['component']);
        $this->assertNotEmpty($page['props']['suppliers']);

        $first = $page['props']['suppliers'][0];

        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('contact_name', $first);
        $this->assertArrayHasKey('phone', $first);
        $this->assertArrayHasKey('address', $first);
        $this->assertArrayHasKey('lead_time_days', $first);
        $this->assertArrayHasKey('term_of_payment', $first);
    }

    public function test_supplier_page_opens_create_form_from_query_string(): void
    {
        $this->assertTrue(
            $this->inertiaPage($this->actingAs($this->owner())->get('/suppliers?new=1'))['props']['openCreate'],
        );

        $this->assertFalse(
            $this->inertiaPage($this->actingAs($this->owner())->get('/suppliers'))['props']['openCreate'],
        );
    }

    public function test_owner_can_create_supplier_with_lead_time_and_top(): void
    {
        $this->actingAs($this->owner())
            ->post('/suppliers', [
                'name' => 'PT Pemasok Cepat',
                'contact_name' => 'Rudi',
                'phone' => '021-1234567',
                'address' => 'Jl. Pemasok No. 5',
                'lead_time_days' => 3,
                'term_of_payment' => 30,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT Pemasok Cepat',
            'lead_time_days' => 3,
            'term_of_payment' => 30,
        ]);
    }

    public function test_owner_can_update_supplier_terms(): void
    {
        $supplier = Supplier::firstOrFail();

        $this->actingAs($this->owner())
            ->put("/suppliers/{$supplier->id}", [
                'name' => $supplier->name,
                'contact_name' => $supplier->contact_name,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'lead_time_days' => 7,
                'term_of_payment' => 7,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $supplier->fresh();

        $this->assertSame(7, $fresh->lead_time_days);
        $this->assertSame(7, $fresh->term_of_payment);
    }

    public function test_term_of_payment_must_be_number_of_days(): void
    {
        $this->actingAs($this->owner())
            ->post('/suppliers', [
                'name' => 'Supplier TOP Salah',
                'lead_time_days' => 2,
                'term_of_payment' => 'NET 30',
            ])
            ->assertSessionHasErrors('term_of_payment');

        $this->assertDatabaseMissing('suppliers', ['name' => 'Supplier TOP Salah']);
    }

    public function test_lead_time_out_of_range_is_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/suppliers', [
                'name' => 'Supplier Lead Time aneh',
                'lead_time_days' => 999,
            ])
            ->assertSessionHasErrors('lead_time_days');

        $this->assertDatabaseMissing('suppliers', ['name' => 'Supplier Lead Time aneh']);
    }

    /**
     * Supplier yang sudah dipakai pembelian tidak boleh dihapus (RESTRICT).
     */
    public function test_supplier_used_in_purchases_cannot_be_deleted(): void
    {
        $supplier = Supplier::whereHas('purchases')->firstOrFail();

        $this->actingAs($this->owner())
            ->delete("/suppliers/{$supplier->id}")
            ->assertSessionHas('error');

        $this->assertStringContainsString('pembelian', session('error'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_without_purchases_can_be_deleted(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier Tanpa Pembelian']);

        $this->actingAs($this->owner())
            ->delete("/suppliers/{$supplier->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_manager_is_forbidden_on_supplier_database(): void
    {
        $supplier = Supplier::firstOrFail();
        $manager = $this->manager();

        $this->actingAs($manager)->get('/suppliers')->assertForbidden();
        $this->actingAs($manager)
            ->post('/suppliers', ['name' => 'Dari Manajer'])
            ->assertForbidden();
        $this->actingAs($manager)->delete("/suppliers/{$supplier->id}")->assertForbidden();

        $this->assertDatabaseMissing('suppliers', ['name' => 'Dari Manajer']);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
