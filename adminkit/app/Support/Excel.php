<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Satu pintu untuk unduhan dan pembacaan berkas Excel (.xlsx). */
class Excel
{
    /** Unduh lembar kerja: header tebal, baris pertama dibekukan, lebar kolom otomatis. */
    public static function download(string $filename, array $headers, iterable $rows, string $sheetTitle = 'Data'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));
        $sheet->fromArray($headers, null, 'A1');

        $line = 2;

        foreach ($rows as $row) {
            $col = 1;

            foreach ($row as $value) {
                if (is_int($value) || is_float($value)) {
                    $sheet->setCellValue([$col, $line], $value);
                } else {
                    $sheet->setCellValueExplicit([$col, $line], (string) ($value ?? ''), DataType::TYPE_STRING);
                }

                $col++;
            }

            $line++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($headers)));

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EEF2F7');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}".max(1, $line - 1));

        for ($col = 1; $col <= count($headers); $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Baris-baris berkas Excel sebagai array (indeks numerik, tanpa kunci kolom). */
    public static function rows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();

        return array_values(array_filter(
            $rows,
            fn ($row) => trim(implode('', array_map(fn ($v) => (string) $v, $row))) !== '',
        ));
    }

    /** Nama berkas dengan cap waktu, mis. pengguna-20260615-0930.xlsx. */
    public static function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd-Hi').'.xlsx';
    }
}
