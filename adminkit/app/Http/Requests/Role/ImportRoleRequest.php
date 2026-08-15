<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class ImportRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:512'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas'];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Berkas impor harus berformat CSV.',
            'file.max' => 'Berkas impor maksimal 512 KB.',
        ];
    }
}
