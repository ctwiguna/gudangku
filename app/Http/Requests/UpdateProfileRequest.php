<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Role dan status aktif sengaja tidak ada di sini: profil tidak boleh
     * dipakai untuk menaikkan hak akses sendiri.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            // Password lama wajib kalau ingin mengganti password.
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'current_password.required_with' => 'Masukkan password lama untuk mengganti password.',
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ];
    }
}
