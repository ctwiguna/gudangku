<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WarehouseTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function inertiaPage(TestResponse $response): array
    {
        $page = $response->viewData('page');

        if (is_string($page)) {
            return json_decode($page, true) ?? [];
        }

        return json_encode($page) === false ? [] : json_decode(json_encode($page), true) ?? [];
    }

    /**
     * Menu Gudang dipakai bottom navigation owner, jadi route ini wajib ada.
     */
    public function test_owner_can_open_warehouse_hub(): void
    {
        $response = $this->actingAs($this->owner())->get('/warehouse');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Warehouse/Index', $page['component']);
        $this->assertSame(Product::count(), $page['props']['counts']['products']);
        $this->assertSame(Category::count(), $page['props']['counts']['categories']);
        $this->assertSame(Outlet::count(), $page['props']['counts']['outlets']);
        $this->assertSame(Employee::count(), $page['props']['counts']['employees']);
    }

    public function test_manager_is_forbidden_on_warehouse_hub(): void
    {
        $this->actingAs($this->manager())->get('/warehouse')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/warehouse')->assertRedirect('/login');
    }

    /**
     * Tombol "Tambah kategori" dari menu Gudang membuka form kategori langsung.
     */
    public function test_category_page_opens_create_form_from_query_string(): void
    {
        $owner = $this->owner();

        $this->assertTrue(
            $this->inertiaPage($this->actingAs($owner)->get('/master-data?new=1'))['props']['openCreate'],
        );

        // Tanpa parameter, form tidak dibuka otomatis.
        $this->assertFalse(
            $this->inertiaPage($this->actingAs($owner)->get('/master-data'))['props']['openCreate'],
        );
    }

    /**
     * Tombol "Tambah outlet" dari menu Gudang membuka form outlet langsung.
     */
    public function test_outlet_page_opens_create_form_from_query_string(): void
    {
        $owner = $this->owner();

        $this->assertTrue(
            $this->inertiaPage($this->actingAs($owner)->get('/outlets?new=1'))['props']['openCreate'],
        );

        $this->assertFalse(
            $this->inertiaPage($this->actingAs($owner)->get('/outlets'))['props']['openCreate'],
        );
    }
}
