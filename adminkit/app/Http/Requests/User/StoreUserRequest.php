<?php

namespace App\Http\Requests\User;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /** Kode pegawai selalu disimpan dalam huruf besar. */
    protected function prepareForValidation(): void
    {
        foreach (['alias', 'mso_code', 'collector_code'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => mb_strtoupper(trim((string) $this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name' => Rules::personName(),
            'username' => Rules::username($id),
            'email' => Rules::email($id),
            'phone' => Rules::phone($id),
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'office' => Rules::text(100),
            'alias' => Rules::code(3, 'alias', $id),
            'mso_code' => Rules::code(4, 'mso_code', $id),
            'collector_code' => Rules::code(3, 'collector_code', $id),
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
            'office' => 'kantor',
            'alias' => 'alias',
            'mso_code' => 'kode MSO',
            'collector_code' => 'kode kolektor',
            'password' => 'kata sandi',
        ];
    }

    public function messages(): array
    {
        return Rules::messages();
    }
}
