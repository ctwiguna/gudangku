<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', 'unique:suppliers,name'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.unique' => 'Nama supplier ini sudah ada.',
            'name.max' => 'Nama supplier maksimal 100 karakter.',
            'phone.max' => 'Nomor telepon maksimal 32 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
        ];
    }
}
