<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseExportService
{
    /** @var array<int, string> */
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const MONEY_FORMAT = '#,##0';

    private const HEADER_FILL = 'E2E8F0';

    private const SUBTOTAL_FILL = 'F8FAFC';

    /**
     * Menyusun laporan tahunan dalam empat sheet:
     *
     * 1. Ringkasan               — total pengeluaran per outlet per bulan.
     * 2. Per Kategori            — pengeluaran per outlet per bulan per kategori
     *                              (listrik, air, ipal, gaji, sewa, lain) + subtotal.
     * 3. Pembelian Produk        — pembelian per outlet per bulan per kategori produk
     *                              (mis. Gas LPG) + subtotal.
     * 4. Rincian                 — daftar datar untuk pivot.
     *
     * Pengelompokan dilakukan di PHP, bukan lewat fungsi MONTH() di SQL,
     * supaya kueri portabel di MySQL maupun SQLite (lingkungan test).
     */
    public function build(int $year): Spreadsheet
    {
        $outlets = Outlet::ordered()->get(['id', 'name', 'code']);

        $expenses = Expense::query()
            ->with('outlet:id,name,code')
            ->between("{$year}-01-01", "{$year}-12-31")
            ->orderBy('expense_date')
            ->get();

        $purchases = Purchase::query()
            ->whereNotNull('priced_at')
            ->whereBetween('received_at', ["{$year}-01-01", "{$year}-12-31"])
            ->with(['outlet:id,name,code', 'items.product.category:id,name'])
            ->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Ringkasan');

        $this->fillSummary($spreadsheet->getActiveSheet(), $year, $outlets, $this->groupMonthly($expenses));
        $this->fillCategoryBreakdown(
            $spreadsheet->createSheet()->setTitle('Per Kategori'),
            $year,
            $outlets,
            $expenses,
        );
        $this->fillPurchases(
            $spreadsheet->createSheet()->setTitle('Pembelian Produk'),
            $year,
            $outlets,
            $purchases,
        );
        $this->fillDetail($spreadsheet->createSheet()->setTitle('Rincian'), $expenses);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @return array<int, array<int, array{total: float, entries: int}>>
     */
    private function groupMonthly(Collection $expenses): array
    {
        $result = [];

        foreach ($expenses as $expense) {
            $outletId = (int) $expense->outlet_id;
            $month = (int) $expense->expense_date->month;

            $result[$outletId][$month]['total'] = ($result[$outletId][$month]['total'] ?? 0) + (float) $expense->amount;
            $result[$outletId][$month]['entries'] = ($result[$outletId][$month]['entries'] ?? 0) + 1;
        }

        return $result;
    }

    /**
     * @param  Collection<int, Outlet>  $outlets
     * @param  array<int, array<int, array{total: float, entries: int}>>  $monthly
     */
    private function fillSummary(Worksheet $sheet, int $year, Collection $outlets, array $monthly): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(2 + count(self::MONTHS) + 1);

        $sheet->setCellValue('A1', 'Pengeluaran per Outlet per Bulan');
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', $this->subtitle($year));
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        $sheet->setCellValue('A4', 'Outlet');
        $sheet->setCellValue('B4', 'Kode');

        foreach (self::MONTHS as $index => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(3 + $index).'4', $label);
        }

        $totalColumn = Coordinate::stringFromColumnIndex(3 + count(self::MONTHS));
        $sheet->setCellValue("{$totalColumn}4", 'Total');

        $this->styleHeaderRow($sheet, "A4:{$totalColumn}4");

        $row = 5;
        $monthTotals = array_fill(1, count(self::MONTHS), 0.0);
        $grandTotal = 0.0;

        foreach ($outlets as $outlet) {
            $sheet->setCellValue("A{$row}", $outlet->name);
            $sheet->setCellValue("B{$row}", $outlet->code);

            $outletTotal = 0.0;

            foreach (self::MONTHS as $index => $label) {
                $month = $index + 1;
                $column = Coordinate::stringFromColumnIndex(3 + $index);
                $total = $monthly[$outlet->id][$month]['total'] ?? 0.0;

                $sheet->setCellValue("{$column}{$row}", $total);
                $sheet->getStyle("{$column}{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

                $outletTotal += $total;
                $monthTotals[$month] += $total;
            }

            $sheet->setCellValue("{$totalColumn}{$row}", $outletTotal);
            $sheet->getStyle("{$totalColumn}{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
            $sheet->getStyle("{$totalColumn}{$row}")->getFont()->setBold(true);

            $grandTotal += $outletTotal;
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL SEMUA OUTLET');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        foreach (self::MONTHS as $index => $label) {
            $month = $index + 1;
            $column = Coordinate::stringFromColumnIndex(3 + $index);

            $sheet->setCellValue("{$column}{$row}", $monthTotals[$month]);
            $sheet->getStyle("{$column}{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
            $sheet->getStyle("{$column}{$row}")->getFont()->setBold(true);
        }

        $sheet->setCellValue("{$totalColumn}{$row}", $grandTotal);
        $sheet->getStyle("{$totalColumn}{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getStyle("{$totalColumn}{$row}")->getFont()->setBold(true);

        $sheet->getStyle("A4:{$totalColumn}{$row}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B5:{$totalColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(12);

        for ($column = 3; $column <= 3 + count(self::MONTHS); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(13);
        }

        $sheet->freezePane('C5');
    }

    /**
     * Sheet "Per Kategori": pengeluaran per outlet, per bulan, per kategori,
     * lengkap dengan subtotal per bulan dan subtotal per outlet.
     *
     * @param  Collection<int, Outlet>  $outlets
     * @param  Collection<int, Expense>  $expenses
     */
    private function fillCategoryBreakdown(Worksheet $sheet, int $year, Collection $outlets, Collection $expenses): void
    {
        $headers = ['Outlet', 'Kode', 'Bulan', 'Kategori', 'Jumlah Entri', 'Total'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $label);
        }

        $this->styleHeaderRow($sheet, 'A1:F1');

        $sheet->setCellValue('A2', $this->subtitle($year));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        $row = 4;

        foreach ($outlets as $outlet) {
            $outletExpenses = $expenses->where('outlet_id', $outlet->id);

            if ($outletExpenses->isEmpty()) {
                continue;
            }

            $outletTotal = 0.0;

            foreach (self::MONTHS as $index => $label) {
                $month = $index + 1;
                $monthExpenses = $outletExpenses->filter(
                    fn (Expense $expense) => (int) $expense->expense_date->month === $month,
                );

                if ($monthExpenses->isEmpty()) {
                    continue;
                }

                $monthTotal = 0.0;

                foreach ($monthExpenses->groupBy(fn (Expense $expense) => $expense->category->value) as $group) {
                    /** @var Expense $first */
                    $first = $group->first();
                    $sum = (float) $group->sum('amount');

                    $sheet->setCellValue("A{$row}", $outlet->name);
                    $sheet->setCellValue("B{$row}", $outlet->code);
                    $sheet->setCellValue("C{$row}", $label);
                    $sheet->setCellValue("D{$row}", $first->category->label());
                    $sheet->setCellValue("E{$row}", $group->count());
                    $sheet->setCellValue("F{$row}", $sum);
                    $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

                    $monthTotal += $sum;
                    $row++;
                }

                // Subtotal bulan ini untuk outlet ini.
                $sheet->setCellValue("D{$row}", "Subtotal {$label}");
                $sheet->setCellValue("E{$row}", $monthExpenses->count());
                $sheet->setCellValue("F{$row}", $monthTotal);
                $this->styleSubtotal($sheet, "D{$row}:F{$row}", "F{$row}");

                $outletTotal += $monthTotal;
                $row++;
            }

            // Subtotal satu outlet untuk seluruh tahun.
            $sheet->setCellValue("A{$row}", $outlet->name);
            $sheet->setCellValue("B{$row}", $outlet->code);
            $sheet->setCellValue("D{$row}", 'TOTAL TAHUN INI');
            $sheet->setCellValue("E{$row}", $outletExpenses->count());
            $sheet->setCellValue("F{$row}", $outletTotal);
            $this->styleSubtotal($sheet, "A{$row}:F{$row}", "F{$row}", true);
            $row += 2;
        }

        if ($row === 4) {
            $sheet->setCellValue('A4', 'Belum ada pengeluaran pada tahun ini.');
        }

        $sheet->getStyle('A1:F'.max(1, $row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);

        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A1:F1');
    }

    /**
     * Sheet "Pembelian Produk": pembelian per outlet, per bulan, per kategori
     * produk (Gas LPG, Deterjen, dan seterusnya) beserta subtotalnya.
     *
     * @param  Collection<int, Outlet>  $outlets
     * @param  Collection<int, Purchase>  $purchases
     */
    private function fillPurchases(Worksheet $sheet, int $year, Collection $outlets, Collection $purchases): void
    {
        $headers = ['Outlet', 'Kode', 'Bulan', 'Kategori Produk', 'Jumlah Item', 'Total Pembelian'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $label);
        }

        $this->styleHeaderRow($sheet, 'A1:F1');

        $sheet->setCellValue('A2', $this->subtitle($year).' · hanya pembelian yang sudah dihargai');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        $row = 4;

        foreach ($outlets as $outlet) {
            $outletPurchases = $purchases->where('outlet_id', $outlet->id);

            if ($outletPurchases->isEmpty()) {
                continue;
            }

            $outletTotal = 0.0;

            foreach (self::MONTHS as $index => $label) {
                $month = $index + 1;

                // Baris item pembelian pada bulan ini untuk outlet ini.
                $items = $outletPurchases
                    ->filter(fn (Purchase $purchase) => (int) $purchase->received_at->month === $month)
                    ->flatMap(fn (Purchase $purchase) => $purchase->items->map(fn ($item) => [
                        'quantity' => (int) $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                        'category' => $item->product?->category?->name ?? 'Tanpa kategori',
                    ]));

                if ($items->isEmpty()) {
                    continue;
                }

                $monthTotal = 0.0;

                foreach ($items->groupBy('category') as $categoryName => $group) {
                    $sum = (float) $group->sum('subtotal');

                    $sheet->setCellValue("A{$row}", $outlet->name);
                    $sheet->setCellValue("B{$row}", $outlet->code);
                    $sheet->setCellValue("C{$row}", $label);
                    $sheet->setCellValue("D{$row}", $categoryName);
                    $sheet->setCellValue("E{$row}", (int) $group->sum('quantity'));
                    $sheet->setCellValue("F{$row}", $sum);
                    $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

                    $monthTotal += $sum;
                    $row++;
                }

                $sheet->setCellValue("D{$row}", "Subtotal {$label}");
                $sheet->setCellValue("E{$row}", (int) $items->sum('quantity'));
                $sheet->setCellValue("F{$row}", $monthTotal);
                $this->styleSubtotal($sheet, "D{$row}:F{$row}", "F{$row}");

                $outletTotal += $monthTotal;
                $row++;
            }

            $sheet->setCellValue("A{$row}", $outlet->name);
            $sheet->setCellValue("B{$row}", $outlet->code);
            $sheet->setCellValue("D{$row}", 'TOTAL TAHUN INI');
            $sheet->setCellValue("E{$row}", (int) $outletPurchases->sum(fn (Purchase $purchase) => $purchase->items->sum('quantity')));
            $sheet->setCellValue("F{$row}", $outletTotal);
            $this->styleSubtotal($sheet, "A{$row}:F{$row}", "F{$row}", true);
            $row += 2;
        }

        if ($row === 4) {
            $sheet->setCellValue('A4', 'Belum ada pembelian berharga pada tahun ini.');
        }

        $sheet->getStyle('A1:F'.max(1, $row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(18);

        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A1:F1');
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     */
    private function fillDetail(Worksheet $sheet, Collection $expenses): void
    {
        $headers = ['Outlet', 'Kode', 'Bulan', 'Kategori', 'Jumlah Entri', 'Total'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $label);
        }

        $this->styleHeaderRow($sheet, 'A1:F1');

        $row = 2;

        $grouped = $expenses
            ->groupBy(fn (Expense $expense) => sprintf(
                '%d-%02d-%s',
                $expense->outlet_id,
                (int) $expense->expense_date->month,
                $expense->category->value,
            ))
            ->sortKeys();

        foreach ($grouped as $key => $group) {
            /** @var Expense $first */
            $first = $group->first();
            $month = (int) $first->expense_date->month;

            $sheet->setCellValue("A{$row}", $first->outlet?->name);
            $sheet->setCellValue("B{$row}", $first->outlet?->code);
            $sheet->setCellValue("C{$row}", self::MONTHS[$month - 1]);
            $sheet->setCellValue("D{$row}", $first->category->label());
            $sheet->setCellValue("E{$row}", $group->count());
            $sheet->setCellValue("F{$row}", (float) $group->sum('amount'));

            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

            $row++;
        }

        if ($row === 2) {
            $sheet->setCellValue('A2', 'Belum ada pengeluaran pada tahun ini.');
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.max(1, $row - 1));

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);
    }

    private function subtitle(int $year): string
    {
        return "Tahun {$year} · semua angka dalam rupiah · dibuat "
            .Carbon::now('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
    }

    private function styleHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::HEADER_FILL);
    }

    private function styleSubtotal(Worksheet $sheet, string $range, string $moneyCell, bool $strong = false): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::SUBTOTAL_FILL);

        $sheet->getStyle($moneyCell)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

        if ($strong) {
            $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        }
    }
}
