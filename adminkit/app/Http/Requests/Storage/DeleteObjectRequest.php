<?php

namespace App\Http\Requests\Storage;

use Illuminate\Foundation\Http\FormRequest;

class DeleteObjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'paths' => ['required', 'array', 'min:1', 'max:50'],
            'paths.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['paths' => 'daftar berkas'];
    }
}
