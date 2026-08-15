<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class ImportRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:1024'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas'];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Berkas impor harus berformat Excel (.xlsx atau .xls).',
            'file.max' => 'Berkas impor maksimal 1 MB.',
        ];
    }
}
