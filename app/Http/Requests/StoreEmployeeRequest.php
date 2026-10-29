<?php

namespace App\Http\Requests;

use App\Enums\SalaryUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            // Karyawan dikelompokkan per outlet, jadi outlet wajib dipilih.
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'name' => ['required', 'string', 'max:150'],
            'salary' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'salary_unit' => ['required', Rule::enum(SalaryUnit::class)],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Pilih outlet untuk karyawan ini.',
            'outlet_id.exists' => 'Outlet yang dipilih tidak ditemukan.',
            'name.required' => 'Nama karyawan wajib diisi.',
            'name.max' => 'Nama karyawan maksimal 150 karakter.',
            'salary.required' => 'Gaji wajib diisi.',
            'salary.numeric' => 'Gaji harus berupa angka.',
            'salary.min' => 'Gaji tidak boleh negatif.',
            'salary_unit.required' => 'Pilih satuan gaji.',
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
            'salary' => 'gaji',
            'salary_unit' => 'satuan gaji',
        ];
    }
}
