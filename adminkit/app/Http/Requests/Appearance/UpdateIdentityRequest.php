<?php

namespace App\Http\Requests\Appearance;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIdentityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'app_name' => Rules::text(60, true),
            'tagline' => Rules::text(100),
            'brand_initials' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z0-9\/<>._-]+$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'app_name' => 'nama aplikasi',
            'tagline' => 'tagline',
            'brand_initials' => 'inisial brand',
        ];
    }
}
