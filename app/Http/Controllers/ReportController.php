<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Purchase;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /**
     * Laporan ringkas owner: pengeluaran per kategori dan outlet,
     * hutang supplier, nilai stok, dan ringkasan pembelian per periode.
     * Hanya owner yang boleh membuka halaman ini.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isOwner(), 403, 'Laporan hanya untuk owner.');

        $today = Carbon::now('Asia/Jakarta');

        $from = $this->validDate($request->string('from')->toString())
            ?? $today->copy()->startOfMonth()->toDateString();
        $to = $this->validDate($request->string('to')->toString())
            ?? $today->copy()->endOfMonth()->toDateString();

        // Jaga urutan tanggal kalau pengguna membalik isian.
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $outletId = $request->integer('outlet') ?: null;

        $expenseQuery = Expense::query()
            ->between($from, $to)
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId));

        $purchaseQuery = Purchase::query()
            ->priced()
            ->whereBetween('received_at', [$from, $to])
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId));

        $debtQuery = Purchase::query()
            ->unpaid()
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId));

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'outlet' => $outletId,
            ],
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'summary' => [
                'expenses_total' => (float) (clone $expenseQuery)->sum('amount'),
                'expenses_count' => (clone $expenseQuery)->count(),
                'purchases_total' => (float) (clone $purchaseQuery)->sum('total'),
                'purchases_count' => (clone $purchaseQuery)->count(),
                'debt_total' => (float) (clone $debtQuery)->sum('total'),
                'debt_count' => (clone $debtQuery)->count(),
                'stock_value' => $outletId === null
                    ? $this->dashboard->stockValue()
                    : $this->dashboard->stockValueForOutlet($outletId),
            ],
            'expensesByCategory' => $this->expensesByCategory($from, $to, $outletId),
            'expensesByOutlet' => $this->expensesByOutlet($from, $to, $outletId),
            'recentExpenses' => (clone $expenseQuery)
                ->with('outlet:id,name')
                ->latestFirst()
                ->limit(10)
                ->get()
                ->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'expense_date' => $expense->expense_date?->toDateString(),
                    'category_label' => $expense->category->label(),
                    'outlet' => $expense->outlet?->name,
                    'amount' => (float) $expense->amount,
                    'note' => $expense->note,
                ])
                ->all(),
        ]);
    }

    private function validDate(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * @return array<int, array{label: string, total: float}>
     */
    private function expensesByCategory(string $from, string $to, ?int $outletId): array
    {
        return Expense::query()
            ->between($from, $to)
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId))
            ->selectRaw('category, sum(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->category->label(),
                'total' => (float) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{outlet: string, total: float}>
     */
    private function expensesByOutlet(string $from, string $to, ?int $outletId): array
    {
        return Expense::query()
            ->between($from, $to)
            ->when($outletId !== null, fn ($query) => $query->forOutlet($outletId))
            ->selectRaw('outlet_id, sum(amount) as total')
            ->groupBy('outlet_id')
            ->with('outlet:id,name')
            ->get()
            ->map(fn ($row) => [
                'outlet' => $row->outlet?->name ?? 'Outlet tidak ditemukan',
                'total' => (float) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
