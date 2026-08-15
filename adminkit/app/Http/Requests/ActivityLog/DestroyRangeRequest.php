<?php

namespace App\Http\Requests\ActivityLog;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class DestroyRangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_from' => Rules::date(),
            'date_to' => [...Rules::date(), 'after_or_equal:date_from'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'tanggal awal',
            'date_to' => 'tanggal akhir',
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            'date_format' => 'Format :attribute harus YYYY-MM-DD.',
        ];
    }
}
