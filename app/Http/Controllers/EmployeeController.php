<?php

namespace App\Http\Controllers;

use App\Enums\SalaryUnit;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Master data karyawan: nama, gaji, dan satuan gaji.
     * Hanya owner. Ini data acuan, bukan transaksi pengeluaran.
     */
    public function index(): Response
    {
        abort_unless(request()->user()?->isOwner(), 403, 'Kelola karyawan hanya untuk owner.');

        return Inertia::render('Employees/Index', [
            'employees' => Employee::ordered()->get()->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'salary' => (float) $employee->salary,
                'salary_unit' => $employee->salary_unit->value,
                'salary_unit_label' => $employee->salary_unit->label(),
                'is_active' => $employee->is_active,
                'note' => $employee->note,
            ]),
            'salaryUnits' => SalaryUnit::options(),
            'summary' => [
                'total' => Employee::count(),
                'active' => Employee::where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return back()->with('success', "Karyawan {$employee->name} tersimpan.");
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return back()->with('success', "Perubahan karyawan {$employee->name} tersimpan.");
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        abort_unless(request()->user()?->isOwner(), 403, 'Kelola karyawan hanya untuk owner.');

        $employee->update(['is_active' => ! $employee->is_active]);

        $state = $employee->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Karyawan {$employee->name} {$state}.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        abort_unless(request()->user()?->isOwner(), 403, 'Kelola karyawan hanya untuk owner.');

        $name = $employee->name;
        $employee->delete();

        return back()->with('success', "Karyawan {$name} dihapus.");
    }
}
