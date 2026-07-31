<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

class DeleteServiceCategoryAction
{
    public function execute(ServiceCategory $category): void
    {
        DB::transaction(function () use ($category) {
            // The FK's nullOnDelete() only fires on a real SQL DELETE, but ServiceCategory
            // is soft-deleted (an UPDATE) — so services must be detached explicitly here,
            // otherwise they're left pointing at a now-invisible category.
            $category->services()->update(['service_category_id' => null]);
            $category->delete();
        });
    }
}
