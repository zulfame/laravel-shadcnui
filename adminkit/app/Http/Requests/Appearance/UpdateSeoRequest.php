<?php

namespace App\Http\Requests\Appearance;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'meta_description' => Rules::text(300),
            'meta_keywords' => Rules::text(200),
            'canonical_url' => Rules::url(),
            'search_indexable' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meta_description' => 'meta description',
            'meta_keywords' => 'meta keywords',
            'canonical_url' => 'canonical URL',
        ];
    }
}
