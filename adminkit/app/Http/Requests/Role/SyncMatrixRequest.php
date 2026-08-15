<?php

namespace App\Http\Requests\Role;

use App\Support\Modules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncMatrixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['string', Rule::in(Modules::permissions())],
        ];
    }

    public function attributes(): array
    {
        return ['matrix' => 'matriks hak akses'];
    }
}
