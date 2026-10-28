<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
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
            'note' => ['nullable', 'string', 'max:255'],
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'counts.*.counted_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'counts.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Pilih outlet lebih dulu.',
            'counts.required' => 'Tidak ada produk yang dihitung.',
            'counts.min' => 'Tidak ada produk yang dihitung.',
            'counts.*.counted_qty.required' => 'Jumlah hitung wajib diisi untuk setiap produk.',
            'counts.*.counted_qty.integer' => 'Jumlah hitung harus berupa angka bulat.',
            'counts.*.counted_qty.min' => 'Jumlah hitung tidak boleh negatif.',
            'counts.*.product_id.distinct' => 'Produk yang sama tidak boleh dihitung dua kali.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'counts.*.counted_qty' => 'jumlah hitung',
        ];
    }

    /**
     * Semua produk aktif harus ikut dihitung. Kalau ada yang tertinggal,
     * stoknya tidak akan disesuaikan dan opname jadi menyesatkan.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $submitted = collect($this->input('counts', []))
                    ->pluck('product_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id);

                $active = Product::active()->pluck('id');

                if ($active->diff($submitted)->isNotEmpty()) {
                    $validator->errors()->add(
                        'counts',
                        'Ada produk aktif yang belum dihitung. Muat ulang halaman lalu isi semua produk.',
                    );
                }
            },
        ];
    }
}
