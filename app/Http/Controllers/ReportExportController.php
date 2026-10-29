<?php

namespace App\Http\Controllers;

use App\Services\ExpenseExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __construct(private readonly ExpenseExportService $export)
    {
    }

    /**
     * Ekspor pengeluaran per outlet per bulan ke berkas .xlsx.
     *
     * Owner-only: laporan keuangan tidak boleh diunduh manajer.
     * Tahun divalidasi; nilai di luar rentang wajar jatuh ke tahun berjalan.
     */
    public function expenses(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->isOwner(), 403, 'Ekspor laporan hanya untuk owner.');

        $year = $request->integer('year');
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }

        $spreadsheet = $this->export->build($year);

        $path = tempnam(sys_get_temp_dir(), 'gudangku-xlsx-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download($path, "pengeluaran-per-outlet-{$year}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }
}
