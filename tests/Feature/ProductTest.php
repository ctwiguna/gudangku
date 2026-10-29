<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductTest extends TestCase
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

    private function managerPusat(): User
    {
        return User::where('email', 'ina@gudangku.test')->firstOrFail();
    }

    private function managerCabang(): User
    {
        return User::where('email', 'salsi@gudangku.test')->firstOrFail();
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

    public function test_owner_product_list_includes_price(): void
    {
        $response = $this->actingAs($this->owner())->get('/products');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Products/Index', $page['component']);
        $this->assertTrue($page['props']['canManage']);

        $first = $page['props']['products'][0];

        $this->assertArrayHasKey('price_buy', $first);
        $this->assertArrayHasKey('stock', $first);
    }

    /**
     * Otorisasi tingkat field: manajer tidak menerima harga sama sekali.
     */
    public function test_manager_product_list_has_no_price_field(): void
    {
        $response = $this->actingAs($this->managerPusat())->get('/products');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertFalse($page['props']['canManage']);
        $this->assertEmpty($page['props']['outlets']);

        foreach ($page['props']['products'] as $product) {
            $this->assertArrayNotHasKey('price_buy', $product);
            $this->assertArrayNotHasKey('price', $product);
        }

        $this->assertStringNotContainsString('price_buy', json_encode($page['props']));
    }

    public function test_manager_stock_is_scoped_to_own_outlet(): void
    {
        $pusat = Outlet::where('code', 'KDM-01')->firstOrFail();
        $cabang = Outlet::where('code', 'TMB-01')->firstOrFail();
        $product = Product::where('sku', 'BTL-PRF')->firstOrFail();

        $pagePusat = $this->inertiaPage($this->actingAs($this->managerPusat())->get('/products'));
        $pageCabang = $this->inertiaPage($this->actingAs($this->managerCabang())->get('/products'));

        $stockPusat = collect($pagePusat['props']['products'])->firstWhere('id', $product->id)['stock'];
        $stockCabang = collect($pageCabang['props']['products'])->firstWhere('id', $product->id)['stock'];

        $this->assertSame($product->stockIn($pusat->id), $stockPusat);
        $this->assertSame($product->stockIn($cabang->id), $stockCabang);
        $this->assertNotSame($stockPusat, $stockCabang);
    }

    public function test_owner_can_create_product(): void
    {
        $category = Category::firstOrFail();

        $this->actingAs($this->owner())
            ->post('/products', [
                'category_id' => $category->id,
                'name' => 'Produk Baru Uji',
                'sku' => 'NEW-001',
                'unit' => 'pcs',
                'min_stock' => 5,
                'price_buy' => 7500,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'NEW-001', 'price_buy' => 7500]);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $existing = Product::firstOrFail();

        $this->actingAs($this->owner())
            ->post('/products', [
                'category_id' => $existing->category_id,
                'name' => 'Duplikat',
                'sku' => $existing->sku,
                'unit' => 'pcs',
                'min_stock' => 1,
                'price_buy' => 1000,
            ])
            ->assertSessionHasErrors('sku');

        $this->assertSame(1, Product::where('sku', $existing->sku)->count());
    }

    public function test_owner_can_update_product_price(): void
    {
        $product = Product::firstOrFail();

        $this->actingAs($this->owner())
            ->put("/products/{$product->id}", [
                'category_id' => $product->category_id,
                'supplier_id' => $product->supplier_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'min_stock' => $product->min_stock,
                'price_buy' => 19999,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price_buy' => 19999]);
    }

    /**
     * Produk dengan riwayat stok tidak boleh dihapus. Saran perbaikannya
     * adalah menonaktifkan produk, bukan menghapus riwayat.
     */
    public function test_product_with_stock_history_cannot_be_deleted(): void
    {
        $product = Product::whereHas('stockMovements')->firstOrFail();

        $response = $this->actingAs($this->owner())->delete("/products/{$product->id}");

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Nonaktifkan', session('error'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_without_history_can_be_deleted(): void
    {
        $product = Product::create([
            'category_id' => Category::firstOrFail()->id,
            'name' => 'Produk Tanpa Riwayat',
            'sku' => 'TMP-001',
            'unit' => 'pcs',
            'min_stock' => 1,
            'price_buy' => 1000,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner())
            ->delete("/products/{$product->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_owner_can_toggle_product_status(): void
    {
        $product = Product::where('is_active', true)->firstOrFail();

        $this->actingAs($this->owner())
            ->patch("/products/{$product->id}/status")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);

        $this->actingAs($this->owner())->patch("/products/{$product->id}/status");

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    public function test_manager_is_forbidden_on_product_management_routes(): void
    {
        $product = Product::firstOrFail();
        $manager = $this->managerPusat();

        $this->actingAs($manager)->get('/products/create')->assertForbidden();
        $this->actingAs($manager)->get("/products/{$product->id}/edit")->assertForbidden();
        $this->actingAs($manager)->patch("/products/{$product->id}/status")->assertForbidden();

        $this->actingAs($manager)->post('/products', [
            'category_id' => $product->category_id,
            'name' => 'Produk Dari Manajer',
            'sku' => 'MGR-001',
            'unit' => 'pcs',
            'min_stock' => 1,
            'price_buy' => 1000,
        ])->assertForbidden();

        $this->actingAs($manager)->put("/products/{$product->id}", [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'min_stock' => $product->min_stock,
            'price_buy' => 1,
        ])->assertForbidden();

        $this->actingAs($manager)->delete("/products/{$product->id}")->assertForbidden();

        $this->assertDatabaseMissing('products', ['sku' => 'MGR-001']);
        $this->assertDatabaseMissing('products', ['id' => $product->id, 'price_buy' => 1]);
    }
}
