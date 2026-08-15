<?php

namespace App\Http\Requests\Appearance;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'support_email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'footer_text' => Rules::text(200),
        ];
    }

    public function attributes(): array
    {
        return [
            'support_email' => 'email dukungan',
            'footer_text' => 'teks footer',
        ];
    }
}
