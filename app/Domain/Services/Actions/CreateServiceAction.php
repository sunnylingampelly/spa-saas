<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\DTOs\CreateServiceData;
use App\Domain\Services\Models\Service;

class CreateServiceAction
{
    public function execute(CreateServiceData $data): Service
    {
        return Service::create([
            'service_category_id' => $data->serviceCategoryId,
            'name' => $data->name,
            'description' => $data->description,
            'duration_minutes' => $data->durationMinutes,
            'price' => $data->price,
            'offer_price' => $data->offerPrice,
            'gst_rate' => $data->gstRate,
            'hsn_sac_code' => $data->hsnSacCode,
            'commission_type' => $data->commissionType,
            'commission_value' => $data->commissionValue,
            'color_hex' => $data->colorHex,
            'status' => $data->status,
        ]);
    }
}
