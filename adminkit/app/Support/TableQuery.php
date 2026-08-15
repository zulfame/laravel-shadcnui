<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Pembantu tabel sisi server: parameter permintaan & metadata paginasi.
 * Dipakai bersama oleh controller yang menyajikan DataTableCard mode SERVER
 * agar kontraknya identik (DRY) dan controller tetap tipis.
 */
class TableQuery
{
    public const PER_PAGE_MIN = 10;

    public const PER_PAGE_MAX = 50;

    public static function search(Request $request): string
    {
        return trim((string) $request->string('search'));
    }

    public static function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', self::PER_PAGE_MIN), self::PER_PAGE_MIN), self::PER_PAGE_MAX);
    }

    /** Kolom urut yang diizinkan saja, jatuh ke $fallback. */
    public static function sort(Request $request, array $sortable, string $fallback): string
    {
        return in_array($request->string('sort')->value(), $sortable, true)
            ? $request->string('sort')->value()
            : $fallback;
    }

    public static function direction(Request $request): string
    {
        return $request->string('dir')->value() === 'desc' ? 'desc' : 'asc';
    }

    /** Nilai filter; 'all' dianggap tanpa filter. */
    public static function filter(Request $request, string $key): string
    {
        $value = $request->string($key)->value();

        return $value === 'all' ? '' : $value;
    }

    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
