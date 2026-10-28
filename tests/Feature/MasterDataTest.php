<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MasterDataTest extends TestCase
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

        return json_decode(json_encode($page), true) ?? [];
    }

    public function test_owner_can_open_master_data_with_both_tabs(): void
    {
        $response = $this->actingAs($this->owner())->get('/master-data');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('MasterData/Index', $page['component']);
        $this->assertSame('kategori', $page['props']['tab']);
        $this->assertNotEmpty($page['props']['categories']);
        $this->assertNotEmpty($page['props']['suppliers']);
    }

    public function test_supplier_tab_is_selected_from_query_string(): void
    {
        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get('/master-data?tab=supplier'),
        );

        $this->assertSame('supplier', $page['props']['tab']);
    }

    public function test_owner_can_create_category(): void
    {
        $response = $this->actingAs($this->owner())->post('/master-data/kategori', [
            'name' => 'Pewangi laundry',
            'description' => 'Bahan tambahan',
        ]);

        $response->assertRedirect('/master-data?tab=kategori');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => 'Pewangi laundry']);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $existing = Category::firstOrFail();

        $response = $this->actingAs($this->owner())->post('/master-data/kategori', [
            'name' => $existing->name,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Category::where('name', $existing->name)->count());
    }

    public function test_owner_can_update_category(): void
    {
        $category = Category::create(['name' => 'Kategori Sementara']);

        $this->actingAs($this->owner())
            ->put("/master-data/kategori/{$category->id}", [
                'name' => 'Kategori Diperbarui',
                'description' => 'Keterangan baru',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Kategori Diperbarui',
            'description' => 'Keterangan baru',
        ]);
    }

    /**
     * Aturan foreign key RESTRICT: kategori yang dipakai produk tidak boleh hilang.
     * Pesannya menjelaskan langkah perbaikan, bukan error SQL mentah.
     */
    public function test_category_used_by_products_cannot_be_deleted(): void
    {
        $category = Category::whereHas('products')->firstOrFail();

        $response = $this->actingAs($this->owner())
            ->delete("/master-data/kategori/{$category->id}");

        $response->assertRedirect('/master-data?tab=kategori');
        $response->assertSessionHas('error');

        $message = session('error');
        $this->assertStringContainsString('masih dipakai', $message);
        $this->assertStringContainsString($category->name, $message);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = Category::create(['name' => 'Kategori Tanpa Produk']);

        $this->actingAs($this->owner())
            ->delete("/master-data/kategori/{$category->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_owner_can_create_and_update_supplier(): void
    {
        $this->actingAs($this->owner())
            ->post('/master-data/supplier', [
                'name' => 'CV Supplier Baru',
                'contact_name' => 'Dewi',
                'phone' => '021-1234567',
                'address' => 'Jl. Baru No. 1',
            ])
            ->assertSessionHasNoErrors();

        $supplier = Supplier::where('name', 'CV Supplier Baru')->firstOrFail();

        $this->actingAs($this->owner())
            ->put("/master-data/supplier/{$supplier->id}", [
                'name' => 'CV Supplier Baru',
                'contact_name' => 'Dewi Lestari',
                'phone' => '021-7654321',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'contact_name' => 'Dewi Lestari',
            'phone' => '021-7654321',
        ]);
    }

    /**
     * Supplier yang sudah dipakai pembelian tidak boleh dihapus karena
     * riwayat pembelian memakai RESTRICT.
     */
    public function test_supplier_used_in_purchases_cannot_be_deleted(): void
    {
        $supplier = Supplier::whereHas('purchases')->firstOrFail();

        $response = $this->actingAs($this->owner())
            ->delete("/master-data/supplier/{$supplier->id}");

        $response->assertSessionHas('error');
        $this->assertStringContainsString('pembelian', session('error'));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_manager_is_forbidden_on_master_data_routes(): void
    {
        $category = Category::create(['name' => 'Kategori Uji']);
        $manager = $this->manager();

        $this->actingAs($manager)->get('/master-data')->assertForbidden();

        $this->actingAs($manager)->post('/master-data/kategori', [
            'name' => 'Kategori Dari Manajer',
        ])->assertForbidden();

        $this->actingAs($manager)->delete("/master-data/kategori/{$category->id}")->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('categories', ['name' => 'Kategori Dari Manajer']);
    }
}
