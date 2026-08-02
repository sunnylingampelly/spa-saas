<?php

namespace App\Domain\Shared\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExportService
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headings, null, 'A1');
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, "A{$rowNumber}");
            $rowNumber++;
        }

        foreach (range(1, count($headings)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
