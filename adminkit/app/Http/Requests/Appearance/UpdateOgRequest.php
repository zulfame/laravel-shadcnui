<?php

namespace App\Http\Requests\Appearance;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOgRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'og_title' => Rules::text(120),
            'og_description' => Rules::text(300),
        ];
    }

    public function attributes(): array
    {
        return [
            'og_title' => 'OG title',
            'og_description' => 'OG description',
        ];
    }
}
