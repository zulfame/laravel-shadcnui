<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class BulkUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:delete,activate,deactivate'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return ['action' => 'aksi', 'ids' => 'baris terpilih'];
    }
}
