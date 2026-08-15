<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ];
    }

    public function attributes(): array
    {
        return ['avatar' => 'foto profil'];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'Foto profil maksimal 1 MB.',
            'avatar.mimes' => 'Foto profil harus berformat JPG, PNG, atau WEBP.',
        ];
    }
}
