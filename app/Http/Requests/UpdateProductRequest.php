<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:150'],
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'unit' => ['required', 'string', 'max:16'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price_buy' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori produk.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'supplier_id.exists' => 'Supplier yang dipilih tidak ditemukan.',
            'name.required' => 'Nama produk wajib diisi.',
            'sku.required' => 'Kode SKU wajib diisi.',
            'sku.unique' => 'Kode SKU ini sudah dipakai produk lain.',
            'unit.required' => 'Satuan wajib diisi, contoh pcs.',
            'min_stock.required' => 'Stok minimum wajib diisi.',
            'min_stock.min' => 'Stok minimum tidak boleh negatif.',
            'price_buy.required' => 'Harga beli per pcs wajib diisi.',
            'price_buy.min' => 'Harga beli tidak boleh negatif.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'supplier_id' => 'supplier',
            'sku' => 'SKU',
            'min_stock' => 'stok minimum',
            'price_buy' => 'harga beli per pcs',
        ];
    }
}
