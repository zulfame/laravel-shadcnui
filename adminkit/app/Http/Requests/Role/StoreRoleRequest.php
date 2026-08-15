<?php

namespace App\Http\Requests\Role;

use App\Support\Modules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('role')?->id;

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Modules::permissions())],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama peranan',
            'permissions' => 'izin',
        ];
    }
}
