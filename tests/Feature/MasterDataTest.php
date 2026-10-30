<?php

namespace Tests\Feature;

use App\Models\Category;
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

    public function test_owner_can_open_category_page(): void
    {
        $response = $this->actingAs($this->owner())->get('/master-data');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('MasterData/Index', $page['component']);
        $this->assertNotEmpty($page['props']['categories']);
        $this->assertFalse($page['props']['openCreate']);
    }

    public function test_category_page_opens_create_form_from_query_string(): void
    {
        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/master-data?new=1'));

        $this->assertTrue($page['props']['openCreate']);
    }

    public function test_owner_can_create_category(): void
    {
        $response = $this->actingAs($this->owner())->post('/master-data/kategori', [
            'name' => 'Pewangi laundry',
            'description' => 'Bahan tambahan',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => 'Pewangi laundry']);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $existing = Category::firstOrFail();

        $this->actingAs($this->owner())
            ->post('/master-data/kategori', ['name' => $existing->name])
            ->assertSessionHasErrors('name');

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
        ]);
    }

    /**
     * Foreign key RESTRICT: kategori yang dipakai barang tidak boleh hilang.
     * Pesannya menjelaskan langkah perbaikan, bukan error SQL mentah.
     */
    public function test_category_used_by_products_cannot_be_deleted(): void
    {
        $category = Category::whereHas('products')->firstOrFail();

        $response = $this->actingAs($this->owner())
            ->delete("/master-data/kategori/{$category->id}");

        $response->assertSessionHas('error');
        $this->assertStringContainsString('masih dipakai', session('error'));
        $this->assertStringContainsString($category->name, session('error'));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = Category::create(['name' => 'Kategori Tanpa Barang']);

        $this->actingAs($this->owner())
            ->delete("/master-data/kategori/{$category->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_manager_is_forbidden_on_category_routes(): void
    {
        $category = Category::create(['name' => 'Kategori Uji']);
        $manager = $this->manager();

        $this->actingAs($manager)->get('/master-data')->assertForbidden();

        $this->actingAs($manager)->post('/master-data/kategori', [
            'name' => 'Kategori Dari Manajer',
        ])->assertForbidden();

        $this->actingAs($manager)->delete("/master-data/kategori/{$category->id}")->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Kategori Dari Manajer']);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
