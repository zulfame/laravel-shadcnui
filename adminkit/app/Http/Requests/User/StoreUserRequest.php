<?php

namespace App\Http\Requests\User;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => Rules::personName(),
            'username' => Rules::username($id),
            'email' => Rules::email($id),
            'phone' => Rules::phone($id),
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['boolean'],
            'password' => Rules::password($id === null),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'username' => 'nama pengguna',
            'email' => 'alamat email',
            'phone' => 'nomor HP',
            'role' => 'peranan',
            'password' => 'kata sandi',
        ];
    }

    public function messages(): array
    {
        return Rules::messages();
    }
}
