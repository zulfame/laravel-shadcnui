<?php

namespace App\Http\Requests\Appearance;

use Illuminate\Foundation\Http\FormRequest;

class UploadAssetRequest extends FormRequest
{
    public function rules(): array
    {
        $isFavicon = $this->route('key') === 'favicon';

        return [
            'file' => [
                'required',
                'file',
                $isFavicon ? 'mimes:png,ico,svg' : 'image',
                $isFavicon ? 'max:256' : 'max:600',
            ],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas'];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Favicon harus berformat PNG, ICO, atau SVG.',
            'file.max' => 'Ukuran berkas melebihi batas yang diizinkan.',
        ];
    }
}
