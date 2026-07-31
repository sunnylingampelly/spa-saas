<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\ServiceCategory;

class UpdateServiceCategoryAction
{
    public function execute(ServiceCategory $category, array $data): ServiceCategory
    {
        $category->update($data);

        return $category;
    }
}
