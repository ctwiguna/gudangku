<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
     * Kalau filenya tidak valid, ini akan gagal — jadi sekaligus bukti
     * berkas benar-benar .xlsx yang bisa dibaca.
     */
    private function loadDownloadedFile($response)
    {
        return IOFactory::load($response->baseResponse->getFile()->getPathname());
    }

    /**
     * Jumlahkan kolom Total (F) pada baris tertentu yang kolom D-nya cocok.
     */
    private function sumColumnForLabel(Worksheet $sheet, string $outletName, string $label, int $lastRow): float
    {
        $total = 0.0;

        for ($row = 4; $row <= $lastRow; $row++) {
            if ($sheet->getCell("A{$row}")->getValue() === $outletName
                && $sheet->getCell("D{$row}")->getValue() === $label) {
                $total += (float) $sheet->getCell("F{$row}")->getValue();
            }
        }

        return $total;
    }

    private function highestRow(Worksheet $sheet): int
    {
        // Baris data punya nilai di kolom A, sedangkan baris subtotal hanya di D/F.
        for ($row = 200; $row >= 4; $row--) {
            if ($sheet->getCell("A{$row}")->getValue() !== null
                || $sheet->getCell("D{$row}")->getValue() !== null) {
                return $row;
            }
        }

        return 4;
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

        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path, false, null, 0, 2), 0, 2));
    }

    public function test_export_contains_four_sheets_with_expected_headers(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;

        $spreadsheet = $this->loadDownloadedFile(
            $this->actingAs($this->owner())->get("/reports/expenses-export?year={$year}"),
        );

        $this->assertSame(
            ['Ringkasan', 'Per Kategori', 'Pembelian Produk', 'Rincian'],
            $spreadsheet->getSheetNames(),
        );

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $this->assertSame('Outlet', $summary->getCell('A4')->getValue());
        $this->assertSame('Jan', $summary->getCell('C4')->getValue());
        $this->assertSame('Des', $summary->getCell('N4')->getValue());
        $this->assertSame('Total', $summary->getCell('O4')->getValue());

        $category = $spreadsheet->getSheetByName('Per Kategori');
        $this->assertSame('Outlet', $category->getCell('A1')->getValue());
        $this->assertSame('Bulan', $category->getCell('C1')->getValue());
        $this->assertSame('Kategori', $category->getCell('D1')->getValue());
        $this->assertSame('Total', $category->getCell('F1')->getValue());

        $purchases = $spreadsheet->getSheetByName('Pembelian Produk');
        $this->assertSame('Kategori Produk', $purchases->getCell('D1')->getValue());
        $this->assertSame('Total Pembelian', $purchases->getCell('F1')->getValue());
    }

    /**
     * Angka di sheet Ringkasan harus sama dengan database, bukan angka contoh.
     */
    public function test_summary_values_match_database(): void
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

    /**
     * Sheet "Per Kategori": pengeluaran per bulan per kategori di setiap outlet,
     * lengkap dengan subtotal. Total per kategori harus sama dengan database.
     */
    public function test_category_breakdown_matches_database_and_has_subtotals(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;
        $outlet = Outlet::where('code', 'CKR-01')->firstOrFail();

        $spreadsheet = $this->loadDownloadedFile(
            $this->actingAs($this->owner())->get("/reports/expenses-export?year={$year}"),
        );

        $sheet = $spreadsheet->getSheetByName('Per Kategori');
        $lastRow = $this->highestRow($sheet);

        // Ada baris subtotal bulanan dan total tahunan per outlet.
        $labels = [];

        for ($row = 4; $row <= $lastRow; $row++) {
            $labels[] = (string) $sheet->getCell("D{$row}")->getValue();
        }

        $this->assertContains('Subtotal Okt', $labels, 'Subtotal bulan harus ada.');
        $this->assertContains('TOTAL TAHUN INI', $labels, 'Total tahunan per outlet harus ada.');

        // Total per kategori = data database.
        $expected = (float) Expense::between("{$year}-01-01", "{$year}-12-31")
            ->forOutlet($outlet->id)
            ->where('category', 'listrik')
            ->sum('amount');

        $this->assertGreaterThan(0, $expected, 'Data contoh harus punya pengeluaran listrik.');
        $this->assertEqualsWithDelta($expected, $this->sumColumnForLabel($sheet, $outlet->name, 'Listrik', $lastRow), 0.01);

        // Total tahunan outlet = seluruh pengeluaran outlet itu.
        $expectedOutletTotal = (float) Expense::between("{$year}-01-01", "{$year}-12-31")
            ->forOutlet($outlet->id)
            ->sum('amount');

        $totalRow = null;

        for ($row = 4; $row <= $lastRow; $row++) {
            if ($sheet->getCell("D{$row}")->getValue() === 'TOTAL TAHUN INI'
                && $sheet->getCell("A{$row}")->getValue() === $outlet->name) {
                $totalRow = $row;
                break;
            }
        }

        $this->assertNotNull($totalRow, 'Baris total tahunan outlet harus ada.');
        $this->assertEqualsWithDelta($expectedOutletTotal, (float) $sheet->getCell("F{$totalRow}")->getValue(), 0.01);
    }

    /**
     * Sheet "Pembelian Produk": pembelian per kategori produk, misalnya Gas LPG.
     */
    public function test_purchases_sheet_contains_product_categories(): void
    {
        $year = (int) Carbon::now('Asia/Jakarta')->year;
        $gasProduct = Product::where('sku', 'LPG-12')->firstOrFail();
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();

        $spreadsheet = $this->loadDownloadedFile(
            $this->actingAs($this->owner())->get("/reports/expenses-export?year={$year}"),
        );

        $sheet = $spreadsheet->getSheetByName('Pembelian Produk');
        $lastRow = $this->highestRow($sheet);

        $expected = (float) PurchaseItem::query()
            ->where('product_id', $gasProduct->id)
            ->whereHas('purchase', fn ($query) => $query
                ->whereNotNull('priced_at')
                ->whereBetween('received_at', ["{$year}-01-01", "{$year}-12-31"]))
            ->sum('subtotal');

        $this->assertGreaterThan(0, $expected, 'Data contoh harus punya pembelian Gas LPG.');
        $this->assertEqualsWithDelta($expected, $this->sumColumnForLabel($sheet, $pusat->name, 'Gas LPG', $lastRow), 0.01);

        // Baris subtotal juga ada di sheet ini.
        $labels = [];

        for ($row = 4; $row <= $lastRow; $row++) {
            $labels[] = (string) $sheet->getCell("D{$row}")->getValue();
        }

        $this->assertContains('Subtotal Okt', $labels);
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
