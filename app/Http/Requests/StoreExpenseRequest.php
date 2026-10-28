<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'expense_date' => ['required', 'date'],
            // Gaji dicatat per minggu, jadi periode wajib diisi untuk kategori itu.
            'period_start' => ['nullable', 'date', 'required_if:category,gaji', 'before_or_equal:period_end'],
            'period_end' => ['nullable', 'date', 'required_if:category,gaji', 'after_or_equal:period_start'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Pilih outlet lebih dulu.',
            'outlet_id.exists' => 'Outlet yang dipilih tidak ditemukan.',
            'category.required' => 'Pilih kategori pengeluaran.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'period_start.required_if' => 'Gaji dicatat per minggu, jadi awal periode wajib diisi.',
            'period_end.required_if' => 'Gaji dicatat per minggu, jadi akhir periode wajib diisi.',
            'period_start.before_or_equal' => 'Awal periode tidak boleh melewati akhir periode.',
            'period_end.after_or_equal' => 'Akhir periode tidak boleh sebelum awal periode.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal harus lebih dari nol.',
            'note.max' => 'Catatan maksimal 255 karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'outlet_id' => 'outlet',
            'expense_date' => 'tanggal',
            'period_start' => 'awal periode',
            'period_end' => 'akhir periode',
            'amount' => 'nominal',
        ];
    }
}
