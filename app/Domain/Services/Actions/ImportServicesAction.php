<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\DTOs\CreateServiceData;
use App\Domain\Services\Models\ServiceCategory;
use App\Domain\Shared\DTOs\ImportResultData;
use App\Domain\Shared\Services\SpreadsheetImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportServicesAction
{
    public function __construct(
        private readonly SpreadsheetImportService $spreadsheetImportService,
        private readonly CreateServiceAction $createService,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'offer_price' => ['nullable', 'numeric', 'min:0'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hsn_sac_code' => ['nullable', 'string', 'max:10'],
            'commission_type' => ['nullable', 'in:percentage,flat'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function execute(UploadedFile $file): ImportResultData
    {
        $rows = $this->spreadsheetImportService->readRows($file);

        $importedCount = 0;
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $spreadsheetRowNumber = $index + 2;

            $validator = Validator::make($row, self::validationRules());

            if ($validator->fails()) {
                $rowErrors[] = ['row' => $spreadsheetRowNumber, 'errors' => $validator->errors()->all()];

                continue;
            }

            $data = $validator->validated();

            DB::transaction(function () use ($data) {
                $categoryId = $this->resolveCategoryId($data['category'] ?? null);

                $this->createService->execute(new CreateServiceData(
                    name: $data['name'],
                    durationMinutes: (int) $data['duration_minutes'],
                    price: (float) $data['price'],
                    serviceCategoryId: $categoryId,
                    description: $data['description'] ?? null,
                    offerPrice: isset($data['offer_price']) ? (float) $data['offer_price'] : null,
                    gstRate: isset($data['gst_rate']) ? (float) $data['gst_rate'] : 18.00,
                    hsnSacCode: $data['hsn_sac_code'] ?? null,
                    commissionType: $data['commission_type'] ?? 'percentage',
                    commissionValue: isset($data['commission_value']) ? (float) $data['commission_value'] : 0,
                    status: $data['status'] ?? 'active',
                ));
            });

            $importedCount++;
        }

        return new ImportResultData(importedCount: $importedCount, rowErrors: $rowErrors);
    }

    // A spreadsheet only has a category *name*, never the internal ID — find the tenant's
    // existing category by a case-insensitive match, or create it, mirroring the inline
    // "add new category" convenience already on the manual Create Service form.
    private function resolveCategoryId(?string $categoryName): ?int
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return null;
        }

        $category = ServiceCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();

        if ($category) {
            return $category->id;
        }

        return ServiceCategory::create(['name' => $categoryName, 'sort_order' => 0])->id;
    }
}
