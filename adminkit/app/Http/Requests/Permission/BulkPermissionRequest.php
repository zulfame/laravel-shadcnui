<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class BulkPermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'baris terpilih'];
    }
}
