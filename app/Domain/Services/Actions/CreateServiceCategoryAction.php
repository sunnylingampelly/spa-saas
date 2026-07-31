<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\ServiceCategory;

class CreateServiceCategoryAction
{
    public function execute(array $data): ServiceCategory
    {
        return ServiceCategory::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }
}
