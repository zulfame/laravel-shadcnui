<?php

namespace App\Http\Requests\Profile;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->user()->id;

        return [
            'name' => Rules::personName(),
            'username' => Rules::username($id),
            'email' => Rules::email($id),
            'phone' => Rules::phone(),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'username' => 'nama pengguna',
            'email' => 'alamat email',
            'phone' => 'nomor HP',
        ];
    }

    public function messages(): array
    {
        return Rules::messages();
    }
}
