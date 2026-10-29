<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Outlet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseExportService
{
    /** @var array<int, string> */
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const MONEY_FORMAT = '#,##0';

    /**
     * Menyusun laporan "pengeluaran per outlet per bulan" dalam satu tahun.
     *
     * Pengelompokan dilakukan di PHP, bukan lewat fungsi MONTH() di SQL,
     * supaya kueri tetap portabel di MySQL maupun SQLite (lingkungan test).
     */
    public function build(int $year): Spreadsheet
    {
        $outlets = Outlet::ordered()->get(['id', 'name', 'code']);

        $expenses = Expense::query()
            ->with('outlet:id,name,code')
            ->between("{$year}-01-01", "{$year}-12-31")
            ->orderBy('expense_date')
            ->get();

        $monthly = $this->groupMonthly($expenses);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Ringkasan');

        $this->fillSummary($spreadsheet->getActiveSheet(), $year, $outlets, $monthly);
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

        $sheet->setCellValue('A2', "Tahun {$year} · semua angka dalam rupiah · dibuat ".Carbon::now('Asia/Jakarta')->format('d/m/Y H:i').' WIB');
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        // Baris header: Outlet | Kode | Jan..Des | Total
        $sheet->setCellValue('A4', 'Outlet');
        $sheet->setCellValue('B4', 'Kode');

        foreach (self::MONTHS as $index => $label) {
            $column = Coordinate::stringFromColumnIndex(3 + $index);
            $sheet->setCellValue("{$column}4", $label);
        }

        $totalColumn = Coordinate::stringFromColumnIndex(3 + count(self::MONTHS));
        $sheet->setCellValue("{$totalColumn}4", 'Total');

        $firstMonthColumn = 'C';
        $lastMonthColumn = Coordinate::stringFromColumnIndex(2 + count(self::MONTHS));

        $sheet->getStyle("A4:{$totalColumn}4")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$totalColumn}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A4:{$totalColumn}4")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

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

        // Baris total keseluruhan
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

        $sheet->getStyle("A5:{$totalColumn}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("B5:{$totalColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(12);

        for ($column = 3; $column <= 3 + count(self::MONTHS); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(13);
        }

        // Bekukan judul dan kolom outlet
        $sheet->freezePane('C5');
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     */
    private function fillDetail(Worksheet $sheet, Collection $expenses): void
    {
        $headers = ['Outlet', 'Kode', 'Bulan', 'Kategori', 'Jumlah Entri', 'Total'];

        foreach ($headers as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}1", $label);
        }

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        $row = 2;

        // Rincian per outlet, bulan, dan kategori.
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
}
