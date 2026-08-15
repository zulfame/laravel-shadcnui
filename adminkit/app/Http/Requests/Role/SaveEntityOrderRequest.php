<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class SaveEntityOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
        ];
    }

    public function attributes(): array
    {
        return ['order' => 'urutan entitas'];
    }

    public function messages(): array
    {
        return ['order.*.regex' => 'Nama entitas hanya boleh huruf kecil, angka, dan garis bawah.'];
    }
}
