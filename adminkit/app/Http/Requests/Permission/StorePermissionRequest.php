<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('permission')?->id;

        return [
            // Format wajib `entitas.aksi`, mis. `users.view`.
            'name' => [
                'required', 'string', 'min:5', 'max:80', 'lowercase',
                'regex:/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($id),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama izin'];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Nama izin harus berformat entitas.aksi memakai huruf kecil, mis. users.view.',
            'name.lowercase' => 'Nama izin harus huruf kecil.',
        ];
    }
}
