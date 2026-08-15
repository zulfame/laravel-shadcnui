<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class BulkRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'baris terpilih'];
    }
}
