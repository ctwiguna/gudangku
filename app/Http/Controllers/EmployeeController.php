<?php

namespace App\Http\Controllers;

use App\Enums\SalaryUnit;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Master data karyawan, dikelompokkan per outlet.
     * Hanya owner. Ini data acuan, bukan transaksi pengeluaran.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isOwner(), 403, 'Kelola karyawan hanya untuk owner.');

        $outlets = Outlet::ordered()->get(['id', 'name', 'code']);

        $outletId = $request->integer('outlet') ?: null;

        $query = Employee::query()->with('outlet:id,name,code')->orderedByOutlet();

        if ($outletId !== null) {
            $query->forOutlet($outletId);
        }

        $employees = $query->get()->map(fn (Employee $employee) => [
            'id' => $employee->id,
            'outlet_id' => $employee->outlet_id,
            'outlet' => $employee->outlet?->name,
            'outlet_code' => $employee->outlet?->code,
            'name' => $employee->name,
            'salary' => (float) $employee->salary,
            'salary_unit' => $employee->salary_unit->value,
            'salary_unit_label' => $employee->salary_unit->label(),
            'is_active' => $employee->is_active,
            'note' => $employee->note,
        ]);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'outlets' => $outlets,
            'filters' => ['outlet' => $outletId],
            'perOutlet' => $outlets->map(fn (Outlet $outlet) => [
                'id' => $outlet->id,
                'outlet' => $outlet->name,
                'code' => $outlet->code,
                'total' => Employee::forOutlet($outlet->id)->count(),
                'active' => Employee::forOutlet($outlet->id)->where('is_active', true)->count(),
            ]),
            'salaryUnits' => SalaryUnit::options(),
            'summary' => [
                'total' => Employee::count(),
                'active' => Employee::where('is_active', true)->count(),
                'outlets' => $outlets->count(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return back()->with('success', "Karyawan {$employee->name} tersimpan di outlet {$employee->outlet?->name}.");
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
