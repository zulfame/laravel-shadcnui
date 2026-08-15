<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ImportUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
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
            'file.max' => 'Berkas impor maksimal 1 MB.',
        ];
    }
}
