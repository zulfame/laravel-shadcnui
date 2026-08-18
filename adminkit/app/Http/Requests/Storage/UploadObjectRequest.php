<?php

namespace App\Http\Requests\Storage;

use Illuminate\Foundation\Http\FormRequest;

class UploadObjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'folder' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-_\/]*$/'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:51200'],
        ];
    }

    public function attributes(): array
    {
        return ['folder' => 'folder', 'files' => 'berkas'];
    }

    public function messages(): array
    {
        return [
            'folder.regex' => 'Folder hanya boleh huruf kecil, angka, garis bawah, tanda hubung, dan garis miring.',
            'files.max' => 'Maksimal 10 berkas per unggahan.',
            'files.*.max' => 'Setiap berkas maksimal 50 MB.',
        ];
    }
}
