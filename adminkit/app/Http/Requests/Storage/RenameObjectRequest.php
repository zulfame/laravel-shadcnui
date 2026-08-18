<?php

namespace App\Http\Requests\Storage;

use App\Support\FileStorage;
use Illuminate\Foundation\Http\FormRequest;

class RenameObjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'path' => ['bail', 'required', 'string', 'max:255', function (string $attribute, $value, $fail) {
                if (! FileStorage::disk()->exists($value)) {
                    $fail('Berkas tidak ditemukan pada penyimpanan.');
                }
            }],
            'target' => ['bail', 'required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-_\.\/]*$/', function (string $attribute, $value, $fail) {
                if (FileStorage::disk()->exists($value)) {
                    $fail('Sudah ada berkas dengan nama tersebut.');
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return ['path' => 'berkas', 'target' => 'nama baru'];
    }

    public function messages(): array
    {
        return ['target.regex' => 'Nama baru hanya boleh huruf, angka, titik, garis bawah, tanda hubung, dan garis miring.'];
    }
}
