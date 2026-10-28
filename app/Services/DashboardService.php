<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockOpname;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ringkasan untuk owner. Semua nilai uang dihitung di server,
     * dan hanya dikirim ke halaman owner.
     *
     * @return array<string, mixed>
     */
    public function ownerSummary(): array
    {
        $month = Carbon::now('Asia/Jakarta')->format('Y-m');

        return [
            'month' => $month,
            'expenses_this_month' => (float) Expense::forMonth($month)->sum('amount'),
            'expenses_by_category' => $this->expensesByCategory($month),
            'debt_total' => (float) Purchase::unpaid()->sum('total'),
            'debt_count' => Purchase::unpaid()->count(),
            'unpriced_count' => Purchase::unpriced()->count(),
            'stock_value' => $this->stockValue(),
            'below_min_count' => $this->belowMinCount(),
            'outlet_count' => Outlet::count(),
            'opname_done_count' => $this->opnameDoneCount(),
        ];
    }

    /**
     * Status per outlet untuk minggu berjalan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function outletStatuses(): array
    {
        $month = Carbon::now('Asia/Jakarta')->format('Y-m');
        $weekStart = StockOpname::currentWeekStart();

        return Outlet::ordered()->get()->map(function (Outlet $outlet) use ($month, $weekStart): array {
            $lastOpname = StockOpname::forOutlet($outlet->id)->latestWeeks()->first();

            return [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'code' => $outlet->code,
                'is_active' => $outlet->is_active,
                'stock_value' => $this->stockValueForOutlet($outlet->id),
                'below_min_count' => $this->belowMinCountForOutlet($outlet->id),
                'opname_done' => StockOpname::forOutlet($outlet->id)->forWeek($weekStart)->exists(),
                'last_opname_week' => $lastOpname?->week_start?->toDateString(),
                'expenses_this_month' => (float) Expense::forOutlet($outlet->id)->forMonth($month)->sum('amount'),
                'debt' => (float) Purchase::forOutlet($outlet->id)->unpaid()->sum('total'),
                'unpriced' => Purchase::forOutlet($outlet->id)->unpriced()->count(),
            ];
        })->all();
    }

    /**
     * Produk dengan saldo di bawah minimum, dikelompokkan per outlet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function belowMinProducts(int $limit = 10): array
    {
        $rows = [];

        foreach (Outlet::active()->ordered()->get() as $outlet) {
            $products = Product::active()->belowMinStock($outlet->id)->get();

            foreach ($products as $product) {
                $rows[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'stock' => (int) $product->stock_qty,
                    'min_stock' => (int) $product->min_stock,
                    'outlet' => $outlet->name,
                ];
            }
        }

        usort($rows, fn (array $a, array $b) => ($a['stock'] - $a['min_stock']) <=> ($b['stock'] - $b['min_stock']));

        return array_slice($rows, 0, $limit);
    }

    /**
     * Penerimaan terakhir. Owner boleh melihat total karena halaman ini owner-only.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentReceipts(int $limit = 6): array
    {
        return Purchase::with(['outlet', 'supplier'])
            ->latestReceived()
            ->limit($limit)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'received_at' => $purchase->received_at?->toDateString(),
                'outlet' => $purchase->outlet?->name,
                'supplier' => $purchase->supplier?->name,
                'invoice_no' => $purchase->invoice_no,
                'total' => (float) $purchase->total,
                'is_priced' => $purchase->isPriced(),
                'is_paid' => $purchase->isPaid(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentExpenses(int $limit = 6): array
    {
        return Expense::with('outlet')
            ->latestFirst()
            ->limit($limit)
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_date' => $expense->expense_date?->toDateString(),
                'outlet' => $expense->outlet?->name,
                'category' => $expense->category->value,
                'category_label' => $expense->category->label(),
                'amount' => (float) $expense->amount,
                'note' => $expense->note,
            ])
            ->all();
    }

    /**
     * @return array<int, array{category: string, label: string, total: float}>
     */
    private function expensesByCategory(string $month): array
    {
        return Expense::forMonth($month)
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

    /** Nilai stok = jumlah saldo dikali harga acuan produk. */
    public function stockValue(): float
    {
        return (float) DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->selectRaw('coalesce(sum(stock_movements.quantity * products.price_buy), 0) as total')
            ->value('total');
    }

    public function stockValueForOutlet(int $outletId): float
    {
        return (float) DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.outlet_id', $outletId)
            ->selectRaw('coalesce(sum(stock_movements.quantity * products.price_buy), 0) as total')
            ->value('total');
    }

    private function belowMinCount(): int
    {
        return array_sum(array_map(
            fn (Outlet $outlet) => $this->belowMinCountForOutlet($outlet->id),
            Outlet::active()->get()->all(),
        ));
    }

    private function belowMinCountForOutlet(int $outletId): int
    {
        return Product::active()->belowMinStock($outletId)->get()->count();
    }

    private function opnameDoneCount(): int
    {
        $weekStart = StockOpname::currentWeekStart();

        return StockOpname::forWeek($weekStart)->count();
    }
}
