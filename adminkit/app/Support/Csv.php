<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Unduhan CSV secara streaming (aman untuk data besar). */
class Csv
{
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Nama berkas dengan cap waktu, mis. pengguna-20260615-0930.csv. */
    public static function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd-Hi').'.csv';
    }
}
