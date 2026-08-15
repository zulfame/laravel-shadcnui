<?php

namespace App\Http\Requests\StorageSetting;

use App\Support\Rules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageRequest extends FormRequest
{
    public function rules(): array
    {
        $needsS3 = $this->input('storage_driver') === 's3';

        return [
            'storage_driver' => ['required', 'in:local,s3'],
            's3_endpoint' => Rules::url($needsS3),
            's3_bucket' => Rules::slug(100, $needsS3),
            's3_path' => Rules::path(),
            's3_key' => [$needsS3 ? 'required' : 'nullable', 'string', 'max:200'],
            's3_secret' => ['nullable', 'string', 'max:200'],
            's3_region' => Rules::slug(50, $needsS3),
            's3_public_url' => Rules::url(),
            's3_path_style' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'storage_driver' => 'driver aktif',
            's3_endpoint' => 'endpoint',
            's3_bucket' => 'bucket',
            's3_path' => 'path',
            's3_key' => 'access key ID',
            's3_secret' => 'secret access key',
            's3_region' => 'region',
            's3_public_url' => 'URL publik',
        ];
    }

    public function messages(): array
    {
        return [
            's3_bucket.regex' => 'Bucket hanya boleh huruf kecil, angka, titik, dan tanda hubung.',
            's3_region.regex' => 'Region hanya boleh huruf kecil, angka, titik, dan tanda hubung.',
            's3_path.regex' => 'Path hanya boleh huruf, angka, garis bawah, tanda hubung, dan garis miring.',
        ];
    }
}
