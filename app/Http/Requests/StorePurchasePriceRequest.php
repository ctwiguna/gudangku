<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'invoice_no' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:purchase_items,id'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Tidak ada item yang bisa dihargai.',
            'items.*.price.required' => 'Harga per pcs wajib diisi untuk setiap item.',
            'items.*.price.numeric' => 'Harga harus berupa angka.',
            'items.*.price.min' => 'Harga tidak boleh negatif.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_no' => 'nomor invoice',
            'items.*.price' => 'harga per pcs',
        ];
    }

    /**
     * Dua penjagaan penting:
     * 1. Item yang dikirim harus benar milik pembelian ini.
     * 2. Semua item harus ikut dikirim, supaya harga tidak bisa diisi sebagian
     *    lalu pembelian dianggap selesai dan masuk hitungan hutang.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $purchase = $this->route('purchase');

                if (! $purchase instanceof Purchase) {
                    return;
                }

                $submitted = collect($this->input('items', []))
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                $owned = $purchase->items()->pluck('id');

                if ($submitted->diff($owned)->isNotEmpty()) {
                    $validator->errors()->add('items', 'Ada baris item yang bukan milik pembelian ini.');
                }

                if ($owned->diff($submitted)->isNotEmpty()) {
                    $validator->errors()->add('items', 'Semua item harus diberi harga sekaligus.');
                }
            },
        ];
    }
}
