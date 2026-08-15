<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('role')?->id;

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:50', 'regex:/^[\pL\pM0-9 .\-]+$/u',
                Rule::unique('roles', 'name')->ignore($id),
            ],
            // Hanya dipakai saat membuat peranan baru: salin hak akses peranan lain.
            'copy_from' => ['nullable', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama peranan', 'copy_from' => 'peranan sumber'];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Nama peranan hanya boleh huruf, angka, spasi, titik, dan tanda hubung.',
        ];
    }
}
