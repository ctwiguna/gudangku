<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExpenseExportTest extends TestCase
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
     * Membaca berkas yang diunduh kembali dengan PhpSpreadsheet.
     * Kalau filenya tidak valid, pemanggilan ini akan gagal — jadi ini
     * sekaligus membuktikan berkas benar-benar .xlsx yang bisa dibaca.
     */
    private function loadDownloadedFile($response)
    {
        $path = $response->baseResponse->getFile()->getPathname();

        return IOFactory::load($path);
    }

    public function test_owner_can_download_expense_excel(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;

        $response = $this->actingAs($this->owner())
            ->get("/reports/expenses-export?year={$year}");

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $this->assertStringContainsString(
            "pengeluaran-per-outlet-{$year}.xlsx",
            (string) $response->headers->get('content-disposition'),
        );

        // Berkas xlsx adalah arsip zip: dua byte pertama harus "PK".
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path, false, null, 0, 2), 0, 2));
    }

    public function test_export_contains_summary_and_detail_sheets(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;

        $spreadsheet = $this->loadDownloadedFile(
            $this->actingAs($this->owner())->get("/reports/expenses-export?year={$year}"),
        );

        $this->assertSame(['Ringkasan', 'Rincian'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $this->assertSame('Outlet', $summary->getCell('A4')->getValue());
        $this->assertSame('Jan', $summary->getCell('C4')->getValue());
        $this->assertSame('Des', $summary->getCell('N4')->getValue());
        $this->assertSame('Total', $summary->getCell('O4')->getValue());

        $detail = $spreadsheet->getSheetByName('Rincian');
        $this->assertSame('Outlet', $detail->getCell('A1')->getValue());
        $this->assertSame('Kategori', $detail->getCell('D1')->getValue());
    }

    /**
     * Angka di berkas harus sama dengan database, bukan angka contoh.
     */
    public function test_exported_values_match_database(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;
        $month = (int) Carbon::now('Asia/Jakarta')->month;
        $outlet = Outlet::where('code', 'CKR-01')->firstOrFail();

        $spreadsheet = $this->loadDownloadedFile(
            $this->actingAs($this->owner())->get("/reports/expenses-export?year={$year}"),
        );

        $sheet = $spreadsheet->getSheetByName('Ringkasan');

        $row = null;

        for ($r = 5; $r <= 25; $r++) {
            if ($sheet->getCell("A{$r}")->getValue() === $outlet->name) {
                $row = $r;
                break;
            }
        }

        $this->assertNotNull($row, 'Baris outlet harus ada di sheet Ringkasan.');

        $expenses = Expense::between("{$year}-01-01", "{$year}-12-31")->forOutlet($outlet->id)->get();
        $expectedMonth = (float) $expenses->filter(fn ($e) => (int) $e->expense_date->month === $month)->sum('amount');
        $expectedYear = (float) $expenses->sum('amount');

        $monthColumn = Coordinate::stringFromColumnIndex(2 + $month);

        $this->assertEqualsWithDelta($expectedMonth, (float) $sheet->getCell("{$monthColumn}{$row}")->getValue(), 0.01);
        $this->assertEqualsWithDelta($expectedYear, (float) $sheet->getCell("O{$row}")->getValue(), 0.01);
    }

    public function test_manager_is_forbidden_to_export(): void
    {
        $this->actingAs($this->manager())
            ->get('/reports/expenses-export')
            ->assertForbidden();
    }

    public function test_invalid_year_falls_back_to_current_year(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;

        $response = $this->actingAs($this->owner())
            ->get('/reports/expenses-export?year=ngawur');

        $response->assertOk();

        $this->assertStringContainsString(
            "pengeluaran-per-outlet-{$year}.xlsx",
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reports/expenses-export')->assertRedirect('/login');
    }
}
