<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('suppliers', 'name')->ignore($this->route('supplier')),
            ],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            // Term of payment dihitung dalam hari, contoh 7, 14, atau 30.
            'term_of_payment' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.unique' => 'Nama supplier ini sudah dipakai supplier lain.',
            'name.max' => 'Nama supplier maksimal 100 karakter.',
            'phone.max' => 'Nomor telepon maksimal 32 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'lead_time_days.integer' => 'Lead time harus berupa angka hari.',
            'lead_time_days.min' => 'Lead time tidak boleh negatif.',
            'lead_time_days.max' => 'Lead time maksimal 365 hari.',
            'term_of_payment.integer' => 'Term of payment harus berupa angka hari.',
            'term_of_payment.min' => 'Term of payment tidak boleh negatif.',
            'term_of_payment.max' => 'Term of payment maksimal 365 hari.',
        ];
    }
}
