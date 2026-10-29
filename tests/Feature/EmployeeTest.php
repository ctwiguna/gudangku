<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EmployeeTest extends TestCase
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

    public function test_owner_can_open_employee_page(): void
    {
        $response = $this->actingAs($this->owner())->get('/employees');

        $response->assertOk();

        $page = $this->inertiaPage($response);

        $this->assertSame('Employees/Index', $page['component']);
        $this->assertCount(3, $page['props']['salaryUnits'], 'Satuan gaji: harian, mingguan, bulanan.');
        $this->assertArrayHasKey('total', $page['props']['summary']);
    }

    public function test_owner_can_create_employee(): void
    {
        $this->actingAs($this->owner())
            ->post('/employees', [
                'name' => 'Siti Karyawan',
                'salary' => 1500000,
                'salary_unit' => 'bulanan',
                'is_active' => true,
                'note' => 'bagian cuci',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'name' => 'Siti Karyawan',
            'salary' => 1500000,
            'salary_unit' => 'bulanan',
            'is_active' => true,
        ]);
    }

    public function test_salary_unit_must_be_one_of_the_allowed_values(): void
    {
        $this->actingAs($this->owner())
            ->post('/employees', [
                'name' => 'Karyawan Satuan Salah',
                'salary' => 100000,
                'salary_unit' => 'per-jam',
            ])
            ->assertSessionHasErrors('salary_unit');

        $this->assertDatabaseMissing('employees', ['name' => 'Karyawan Satuan Salah']);
    }

    public function test_salary_must_be_positive_number(): void
    {
        $this->actingAs($this->owner())
            ->post('/employees', [
                'name' => 'Karyawan Gaji Salah',
                'salary' => -1,
                'salary_unit' => 'harian',
            ])
            ->assertSessionHasErrors('salary');
    }

    public function test_owner_can_update_employee(): void
    {
        $employee = Employee::create([
            'name' => 'Karyawan Lama',
            'salary' => 100000,
            'salary_unit' => 'harian',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner())
            ->put("/employees/{$employee->id}", [
                'name' => 'Karyawan Diperbarui',
                'salary' => 120000,
                'salary_unit' => 'mingguan',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $employee->fresh();

        $this->assertSame('Karyawan Diperbarui', $fresh->name);
        $this->assertSame('mingguan', $fresh->salary_unit->value);
        $this->assertEqualsWithDelta(120000, (float) $fresh->salary, 0.001);
    }

    public function test_owner_can_toggle_employee_status(): void
    {
        $employee = Employee::create([
            'name' => 'Karyawan Toggle',
            'salary' => 90000,
            'salary_unit' => 'harian',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner())
            ->patch("/employees/{$employee->id}/status")
            ->assertSessionHas('success');

        $this->assertFalse($employee->fresh()->is_active);

        $this->actingAs($this->owner())->patch("/employees/{$employee->id}/status");

        $this->assertTrue($employee->fresh()->is_active);
    }

    public function test_owner_can_delete_employee(): void
    {
        $employee = Employee::create([
            'name' => 'Karyawan Dihapus',
            'salary' => 80000,
            'salary_unit' => 'harian',
            'is_active' => false,
        ]);

        $this->actingAs($this->owner())
            ->delete("/employees/{$employee->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_manager_is_forbidden_on_employee_routes(): void
    {
        $employee = Employee::create([
            'name' => 'Karyawan Uji',
            'salary' => 70000,
            'salary_unit' => 'harian',
            'is_active' => true,
        ]);

        $manager = $this->manager();

        $this->actingAs($manager)->get('/employees')->assertForbidden();
        $this->actingAs($manager)->post('/employees', [
            'name' => 'Dari Manajer',
            'salary' => 1,
            'salary_unit' => 'harian',
        ])->assertForbidden();
        $this->actingAs($manager)->put("/employees/{$employee->id}", [
            'name' => 'Diubah manajer',
            'salary' => 1,
            'salary_unit' => 'harian',
        ])->assertForbidden();
        $this->actingAs($manager)->patch("/employees/{$employee->id}/status")->assertForbidden();
        $this->actingAs($manager)->delete("/employees/{$employee->id}")->assertForbidden();

        $this->assertDatabaseMissing('employees', ['name' => 'Dari Manajer']);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Karyawan Uji']);
    }
}
