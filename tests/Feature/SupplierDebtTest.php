<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SupplierDebtTest extends TestCase
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

    public function test_owner_can_open_supplier_debt_detail(): void
    {
        $response = $this->actingAs($this->owner())->get('/supplier-debts');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('SupplierDebts/Index', $page['component']);

        // Angka ringkasan harus sama dengan database.
        $this->assertEqualsWithDelta((float) Purchase::unpaid()->sum('total'), $page['props']['summary']['total'], 0.001);
        $this->assertSame(Purchase::unpaid()->count(), $page['props']['summary']['purchases']);
        $this->assertSame(
            Purchase::unpaid()->distinct()->count('supplier_id'),
            $page['props']['summary']['suppliers'],
        );

        $this->assertNotEmpty($page['props']['suppliers']);
    }

    /**
     * Detail barang per supplier: nama produk, jumlah, harga satuan, subtotal.
     */
    public function test_detail_lists_items_per_supplier(): void
    {
        $purchase = Purchase::unpaid()->whereHas('items')->firstOrFail();

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/supplier-debts'));

        $group = collect($page['props']['suppliers'])
            ->firstWhere('supplier_id', $purchase->supplier_id);

        $this->assertNotNull($group, 'Supplier dari pembelian belum dibayar harus muncul.');
        $this->assertSame($purchase->supplier->name, $group['supplier']);

        $row = collect($group['purchases'])->firstWhere('id', $purchase->id);

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta((float) $purchase->total, $row['total'], 0.001);

        $expectedItem = $purchase->items()->with('product')->first();

        $this->assertSame($expectedItem->product->name, $row['items'][0]['product']);
        $this->assertSame((int) $expectedItem->quantity, $row['items'][0]['quantity']);
        $this->assertEqualsWithDelta((float) $expectedItem->price, $row['items'][0]['price'], 0.001);
        $this->assertEqualsWithDelta((float) $expectedItem->subtotal, $row['items'][0]['subtotal'], 0.001);

        // Subtotal = harga satuan x jumlah.
        $this->assertEqualsWithDelta(
            $row['items'][0]['price'] * $row['items'][0]['quantity'],
            $row['items'][0]['subtotal'],
            0.001,
        );
    }

    public function test_paid_purchase_is_excluded(): void
    {
        $purchase = Purchase::unpaid()->firstOrFail();

        $this->actingAs($this->owner())
            ->patch("/purchases/{$purchase->id}/paid")
            ->assertSessionHas('success');

        $page = $this->inertiaPage($this->actingAs($this->owner())->get('/supplier-debts'));

        $ids = collect($page['props']['suppliers'])
            ->flatMap(fn (array $group) => collect($group['purchases'])->pluck('id'))
            ->all();

        $this->assertNotContains($purchase->id, $ids, 'Pembelian yang sudah dibayar tidak boleh muncul.');
        $this->assertEqualsWithDelta(
            (float) Purchase::unpaid()->sum('total'),
            $page['props']['summary']['total'],
            0.001,
        );
    }

    public function test_can_filter_by_outlet(): void
    {
        $outlet = $this->outlet();

        $page = $this->inertiaPage(
            $this->actingAs($this->owner())->get("/supplier-debts?outlet={$outlet->id}"),
        );

        $this->assertSame($outlet->id, $page['props']['filters']['outlet']);

        $totals = collect($page['props']['suppliers'])->sum('total');

        $this->assertEqualsWithDelta(
            (float) Purchase::unpaid()->forOutlet($outlet->id)->sum('total'),
            $totals,
            0.001,
        );
    }

    public function test_manager_is_forbidden(): void
    {
        $this->actingAs($this->manager())->get('/supplier-debts')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/supplier-debts')->assertRedirect('/login');
    }
}
