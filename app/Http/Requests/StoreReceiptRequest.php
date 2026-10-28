<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Tidak ada aturan untuk harga. Kalau klien mengirim price atau subtotal,
     * nilainya diabaikan karena controller hanya memakai product_id dan quantity.
     * Penerimaan barang selalu dicatat tanpa harga.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'outlet_id' => [
                Rule::requiredIf(fn () => $this->user()?->isOwner() ?? false),
                'nullable',
                'integer',
                'exists:outlets,id',
            ],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'received_at' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Pilih outlet lebih dulu.',
            'supplier_id.required' => 'Pilih supplier barang datang.',
            'received_at.required' => 'Tanggal terima wajib diisi.',
            'items.required' => 'Tambahkan minimal satu barang.',
            'items.min' => 'Tambahkan minimal satu barang.',
            'items.*.product_id.required' => 'Pilih produk pada setiap baris.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditulis dua kali.',
            'items.*.quantity.required' => 'Jumlah wajib diisi.',
            'items.*.quantity.min' => 'Jumlah minimal 1.',
            'items.*.quantity.integer' => 'Jumlah harus berupa angka bulat.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'received_at' => 'tanggal terima',
            'invoice_no' => 'nomor invoice',
        ];
    }
}
