<?php

namespace App\Domain\Shared\DTOs;

use Spatie\LaravelData\Data;

class ImportResultData extends Data
{
    /**
     * @param  array<int, array{row: int, errors: array<int, string>}>  $rowErrors
     */
    public function __construct(
        public readonly int $importedCount,
        public readonly array $rowErrors = [],
    ) {}
}
