<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePermissionRequest extends FormRequest
{
    /** Aksi standar yang tersedia untuk generator. */
    public const ABILITIES = ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];

    public function rules(): array
    {
        return [
            'entity' => ['required', 'string', 'min:3', 'max:40', 'lowercase', 'regex:/^[a-z][a-z0-9_]*$/'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(self::ABILITIES)],
        ];
    }

    public function attributes(): array
    {
        return ['entity' => 'entitas', 'abilities' => 'aksi'];
    }

    public function messages(): array
    {
        return [
            'entity.regex' => 'Entitas hanya boleh huruf kecil, angka, dan garis bawah, mis. projects.',
            'entity.lowercase' => 'Entitas harus huruf kecil.',
        ];
    }
}
