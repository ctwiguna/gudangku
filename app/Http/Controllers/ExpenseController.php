<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Daftar pengeluaran dengan filter bulan dan outlet.
     * Nilai filter dikirim lewat binding parameter, bukan dirangkai ke SQL.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Expense::class);

        $month = $request->string('month')->toString();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? $month
            : Carbon::now('Asia/Jakarta')->format('Y-m');

        $outletId = $request->integer('outlet') ?: null;

        $base = Expense::query()->forMonth($month);

        if ($outletId !== null) {
            $base->forOutlet($outletId);
        }

        $expenses = (clone $base)
            ->with('outlet')
            ->latestFirst()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_date' => $expense->expense_date?->toDateString(),
                'category' => $expense->category->value,
                'category_label' => $expense->category->label(),
                'outlet' => $expense->outlet?->name,
                'amount' => (float) $expense->amount,
                'note' => $expense->note,
                'period_start' => $expense->period_start?->toDateString(),
                'period_end' => $expense->period_end?->toDateString(),
            ]);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => [
                'month' => $month,
                'outlet' => $outletId,
            ],
            'summary' => [
                'total' => (float) (clone $base)->sum('amount'),
                'count' => (clone $base)->count(),
                'by_category' => $this->totalsByCategory($base),
            ],
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'categories' => ExpenseCategory::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Expense::class);

        return Inertia::render('Expenses/Form', [
            'expense' => null,
            'outlets' => Outlet::active()->ordered()->get(['id', 'name', 'code']),
            'categories' => ExpenseCategory::options(),
            'today' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('expenses.index', ['month' => $expense->expense_date?->format('Y-m')])
            ->with('success', 'Pengeluaran '.$expense->category->label().' tersimpan.');
    }

    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        return Inertia::render('Expenses/Form', [
            'expense' => [
                'id' => $expense->id,
                'outlet_id' => $expense->outlet_id,
                'category' => $expense->category->value,
                'expense_date' => $expense->expense_date?->toDateString(),
                'period_start' => $expense->period_start?->toDateString(),
                'period_end' => $expense->period_end?->toDateString(),
                'amount' => (float) $expense->amount,
                'note' => $expense->note,
            ],
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'categories' => ExpenseCategory::options(),
            'today' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        return redirect()
            ->route('expenses.index', ['month' => $expense->expense_date?->format('Y-m')])
            ->with('success', 'Perubahan pengeluaran tersimpan.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $label = $expense->category->label().' '.$expense->expense_date?->toDateString();
        $expense->delete();

        return redirect()
            ->route('expenses.index', ['month' => $expense->expense_date?->format('Y-m')])
            ->with('success', "Pengeluaran {$label} dihapus.");
    }

    /**
     * @return array<int, array{category: string, label: string, total: float}>
     */
    private function totalsByCategory(\Illuminate\Database\Eloquent\Builder $base): array
    {
        return (clone $base)
            ->selectRaw('category, sum(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category->value,
                'label' => $row->category->label(),
                'total' => (float) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
