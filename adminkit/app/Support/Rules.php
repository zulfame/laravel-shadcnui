<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Satu sumber aturan validasi per TIPE kolom.
 * Form Request memakai kelas ini agar aturan seragam di seluruh aplikasi,
 * dan cerminnya ada di `resources/js/lib/validators.js` untuk validasi cepat di UI.
 */
class Rules
{
    /** Nomor telepon: digit, boleh diawali "+", 9–15 digit. */
    public const PHONE = '/^\+?[0-9]{9,15}$/';

    /** Nama orang: huruf (termasuk aksen), spasi, titik, apostrof, tanda hubung. */
    public const PERSON_NAME = "/^[\pL\pM .'\-]+$/u";

    /** Slug teknis: huruf kecil, angka, titik, tanda hubung. */
    public const SLUG = '/^[a-z0-9.\-]+$/';

    /** Path/prefix folder: huruf, angka, garis bawah, tanda hubung, garis miring. */
    public const PATH = '/^[A-Za-z0-9_\-\/]+$/';

    public static function personName(bool $required = true, int $max = 100): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'min:3', "max:{$max}", 'regex:'.self::PERSON_NAME];
    }

    public static function username(?int $ignoreId = null, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable', 'string', 'min:3', 'max:50', 'alpha_dash', 'lowercase',
            Rule::unique('users', 'username')->ignore($ignoreId),
        ];
    }

    public static function email(?int $ignoreId = null, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable', 'string', 'email:rfc', 'max:150',
            Rule::unique('users', 'email')->ignore($ignoreId),
        ];
    }

    public static function phone(?int $ignoreId = null, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable', 'string', 'regex:'.self::PHONE, 'max:16',
            Rule::unique('users', 'phone')->ignore($ignoreId),
        ];
    }

    public static function password(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', Password::min(8)];
    }

    public static function text(int $max, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
    }

    public static function url(bool $required = false, int $max = 200): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'url:http,https', "max:{$max}"];
    }

    public static function slug(int $max = 100, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}", 'regex:'.self::SLUG];
    }

    public static function path(int $max = 120): array
    {
        return ['nullable', 'string', "max:{$max}", 'regex:'.self::PATH];
    }

    public static function date(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'date_format:Y-m-d'];
    }

    /** Pesan Indonesia untuk aturan regex per kolom. */
    public static function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP hanya boleh angka (9–15 digit) dan boleh diawali tanda +.',
            'name.regex' => 'Nama hanya boleh huruf, spasi, titik, apostrof, dan tanda hubung.',
            'username.lowercase' => 'Nama pengguna harus huruf kecil.',
            'username.alpha_dash' => 'Nama pengguna hanya boleh huruf, angka, garis bawah, dan tanda hubung.',
        ];
    }
}
