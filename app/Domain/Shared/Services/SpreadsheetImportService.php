<?php

namespace App\Domain\Shared\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SpreadsheetImportService
{
    /**
     * Reads the first sheet of an uploaded spreadsheet, treating row 1 as column headings.
     * Returns one associative array per subsequent non-blank row, keyed by heading text
     * (trimmed). Date-formatted cells are normalized to "Y-m-d" strings rather than the raw
     * Excel serial number, since that's what every date validation rule in this app expects.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $headings = [];
        foreach ($sheet->getRowIterator(1, 1) as $headerRow) {
            foreach ($headerRow->getCellIterator() as $cell) {
                $headings[$cell->getColumn()] = trim((string) $cell->getValue());
            }
        }

        $rows = [];
        $highestRow = $sheet->getHighestRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = [];
            $hasAnyValue = false;

            foreach ($headings as $column => $heading) {
                if ($heading === '') {
                    continue;
                }

                $cell = $sheet->getCell("{$column}{$rowNumber}");
                $value = $this->cellValue($cell);

                if ($value !== null && $value !== '') {
                    $hasAnyValue = true;
                }

                $row[$heading] = $value;
            }

            if ($hasAnyValue) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function cellValue(Cell $cell): mixed
    {
        $value = $cell->getCalculatedValue();

        if (is_numeric($value) && Date::isDateTime($cell)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            // Excel doesn't distinguish an amount from a purely-numeric-looking piece of text
            // (a phone number, a pincode, an HSN/SAC code) — both come back as a plain number.
            // Every genuinely numeric field in this app is validated with numeric/integer rules,
            // which already accept numeric strings, so normalizing to a string here is what lets
            // text fields that happen to look like numbers still validate as strings correctly.
            return $value == (int) $value ? (string) (int) $value : (string) $value;
        }

        return is_string($value) ? trim($value) : $value;
    }
}
