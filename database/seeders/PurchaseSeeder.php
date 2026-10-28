<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();
        $cabang = Outlet::where('code', 'CKR-02')->firstOrFail();
        $gas = Supplier::where('name', 'PT Sumber Gas Nusantara')->firstOrFail();
        $kemas = Supplier::where('name', 'CV Aroma Kemas Nusantara')->firstOrFail();
        $owner = User::where('email', 'owner@gudangku.test')->firstOrFail();
        $manajerPusat = User::where('email', 'manajer.pusat@gudangku.test')->firstOrFail();
        $manajerCabang = User::where('email', 'manajer.cabang@gudangku.test')->firstOrFail();

        $today = Carbon::now('Asia/Jakarta')->startOfDay();

        $purchases = [
            [
                // Antrean harga: manajer sudah menerima barang, owner belum mengisi harga.
                'invoice_no' => 'INV-SEED-001',
                'outlet' => $pusat,
                'supplier' => $kemas,
                'user' => $manajerPusat,
                'received_at' => $today->copy()->subDays(3),
                'priced_at' => null,
                'paid_at' => null,
                'note' => 'Penerimaan rutin mingguan, menunggu harga owner.',
                'items' => [
                    ['sku' => 'PLK-KCL', 'qty' => 100, 'price' => null],
                    ['sku' => 'PLK-BSR', 'qty' => 50, 'price' => null],
                    ['sku' => 'SLT-01', 'qty' => 20, 'price' => null],
                ],
            ],
            [
                // Hutang supplier: sudah berharga, belum dibayar.
                'invoice_no' => 'INV-SEED-002',
                'outlet' => $pusat,
                'supplier' => $gas,
                'user' => $manajerPusat,
                'received_at' => $today->copy()->subDays(7),
                'priced_at' => $today->copy()->subDays(6),
                'paid_at' => null,
                'note' => 'Gas LPG dan deterjen, masuk hitungan hutang.',
                'items' => [
                    ['sku' => 'LPG-12', 'qty' => 10, 'price' => 21000],
                    ['sku' => 'DTR-JRG', 'qty' => 5, 'price' => 33000],
                ],
            ],
            [
                // Lunas: tidak lagi dihitung sebagai hutang.
                'invoice_no' => 'INV-SEED-003',
                'outlet' => $cabang,
                'supplier' => $kemas,
                'user' => $manajerCabang,
                'received_at' => $today->copy()->subDays(10),
                'priced_at' => $today->copy()->subDays(10),
                'paid_at' => $today->copy()->subDays(9),
                'note' => 'Pembelian packaging cabang, sudah dilunasi.',
                'items' => [
                    ['sku' => 'BTL-PRF', 'qty' => 30, 'price' => 4200],
                    ['sku' => 'BOX-SFT', 'qty' => 12, 'price' => 7800],
                ],
            ],
            [
                // Antrean kedua untuk cabang, supaya dashboard owner punya dua baris.
                'invoice_no' => 'INV-SEED-004',
                'outlet' => $cabang,
                'supplier' => $kemas,
                'user' => $manajerCabang,
                'received_at' => $today->copy()->subDay(),
                'priced_at' => null,
                'paid_at' => null,
                'note' => 'Penerimaan cabang, menunggu harga.',
                'items' => [
                    ['sku' => 'PBR-LNT', 'qty' => 8, 'price' => null],
                    ['sku' => 'BTL-PRR', 'qty' => 10, 'price' => null],
                ],
            ],
        ];

        foreach ($purchases as $data) {
            $total = collect($data['items'])
                ->sum(fn (array $item) => (float) ($item['price'] ?? 0) * $item['qty']);

            $purchase = Purchase::updateOrCreate(
                ['invoice_no' => $data['invoice_no']],
                [
                    'outlet_id' => $data['outlet']->id,
                    'supplier_id' => $data['supplier']->id,
                    'user_id' => $data['user']->id,
                    'received_at' => $data['received_at']->toDateString(),
                    'priced_at' => $data['priced_at']?->toDateString(),
                    'paid_at' => $data['paid_at']?->toDateString(),
                    'total' => $total,
                    'note' => $data['note'],
                ],
            );

            foreach ($data['items'] as $item) {
                $product = Product::where('sku', $item['sku'])->firstOrFail();
                $price = $item['price'];

                $purchase->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'quantity' => $item['qty'],
                        'price' => $price,
                        'subtotal' => $price === null ? 0 : (float) $price * $item['qty'],
                    ],
                );

                // Barang langsung masuk stok pada tanggal terima, walau belum dihargai.
                // firstOrCreate menjaga saldo tidak berlipat saat seeder dijalankan ulang.
                StockMovement::firstOrCreate(
                    [
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'product_id' => $product->id,
                        'type' => MovementType::In,
                    ],
                    [
                        'outlet_id' => $data['outlet']->id,
                        'user_id' => $data['user']->id,
                        'quantity' => $item['qty'],
                        'note' => 'Penerimaan '.$data['invoice_no'],
                        'moved_at' => $data['received_at']->copy()->setTime(9, 0),
                    ],
                );
            }
        }

        // Owner memang boleh membuat pembelian lengkap dengan harga sejak awal.
        $directInvoice = 'INV-SEED-005';
        $direct = Purchase::updateOrCreate(
            ['invoice_no' => $directInvoice],
            [
                'outlet_id' => $pusat->id,
                'supplier_id' => $kemas->id,
                'user_id' => $owner->id,
                'received_at' => $today->copy()->subDays(2)->toDateString(),
                'priced_at' => $today->copy()->subDays(2)->toDateString(),
                'paid_at' => null,
                'total' => 117000,
                'note' => 'Dibuat owner lengkap dengan harga sejak awal.',
            ],
        );

        $directProduct = Product::where('sku', 'PBR-LNT')->firstOrFail();
        $direct->items()->updateOrCreate(
            ['product_id' => $directProduct->id],
            ['quantity' => 6, 'price' => 19500, 'subtotal' => 117000],
        );

        StockMovement::firstOrCreate(
            [
                'reference_type' => Purchase::class,
                'reference_id' => $direct->id,
                'product_id' => $directProduct->id,
                'type' => MovementType::In,
            ],
            [
                'outlet_id' => $pusat->id,
                'user_id' => $owner->id,
                'quantity' => 6,
                'note' => 'Penerimaan '.$directInvoice,
                'moved_at' => $today->copy()->subDays(2)->setTime(10, 0),
            ],
        );
    }
}
