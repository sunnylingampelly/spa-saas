<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BuildsSpreadsheetUploads
{
    /**
     * Builds a real .xlsx file on disk and wraps it as an UploadedFile, so import tests
     * exercise the actual PhpSpreadsheet read path rather than a fake/stub file.
     *
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function makeXlsxUpload(array $headings, array $rows, string $filename = 'import.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headings, null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, "A{$rowNumber}");
            $rowNumber++;
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
