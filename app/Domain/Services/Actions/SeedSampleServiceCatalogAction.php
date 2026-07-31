<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

class SeedSampleServiceCatalogAction
{
    private const CATALOG = [
        'Massage Therapies' => [
            ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000, 'color_hex' => '#6366f1'],
            ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1800, 'color_hex' => '#818cf8'],
            ['name' => 'Thai Massage', 'duration_minutes' => 90, 'price' => 2500, 'color_hex' => '#4f46e5'],
            ['name' => 'Balinese Massage', 'duration_minutes' => 60, 'price' => 2200, 'color_hex' => '#4338ca'],
            ['name' => 'Hot Stone Massage', 'duration_minutes' => 75, 'price' => 2800, 'color_hex' => '#f97316'],
            ['name' => 'Aroma Therapy', 'duration_minutes' => 60, 'price' => 2400, 'color_hex' => '#fb923c'],
            ['name' => 'Sports Massage', 'duration_minutes' => 60, 'price' => 2100, 'color_hex' => '#0ea5e9'],
            ['name' => 'Foot Reflexology', 'duration_minutes' => 45, 'price' => 1200, 'color_hex' => '#10b981'],
            ['name' => 'Head Massage', 'duration_minutes' => 30, 'price' => 900, 'color_hex' => '#14b8a6'],
            ['name' => 'Back Massage', 'duration_minutes' => 30, 'price' => 1000, 'color_hex' => '#0891b2'],
            ['name' => 'Neck Massage', 'duration_minutes' => 20, 'price' => 700, 'color_hex' => '#0284c7'],
            ['name' => 'Hand Massage', 'duration_minutes' => 20, 'price' => 600, 'color_hex' => '#0369a1'],
            ['name' => 'Full Body Massage', 'duration_minutes' => 90, 'price' => 3000, 'color_hex' => '#7c3aed'],
            ['name' => 'Couple Massage', 'duration_minutes' => 90, 'price' => 5000, 'color_hex' => '#db2777'],
        ],
        'Body Treatments' => [
            ['name' => 'Body Polish', 'duration_minutes' => 60, 'price' => 2200, 'color_hex' => '#f59e0b'],
            ['name' => 'Body Scrub', 'duration_minutes' => 45, 'price' => 1800, 'color_hex' => '#eab308'],
            ['name' => 'Body Spa', 'duration_minutes' => 120, 'price' => 3500, 'color_hex' => '#84cc16'],
            ['name' => 'Steam Bath', 'duration_minutes' => 20, 'price' => 600, 'color_hex' => '#64748b'],
            ['name' => 'Jacuzzi', 'duration_minutes' => 30, 'price' => 900, 'color_hex' => '#475569'],
        ],
        'Packages' => [
            ['name' => 'Premium Spa Package', 'duration_minutes' => 150, 'price' => 6000, 'color_hex' => '#be185d'],
        ],
    ];

    public function execute(): void
    {
        if (Service::query()->exists()) {
            return;
        }

        DB::transaction(function () {
            foreach (self::CATALOG as $categoryName => $services) {
                $category = ServiceCategory::create(['name' => $categoryName]);

                foreach ($services as $service) {
                    Service::create([...$service, 'service_category_id' => $category->id]);
                }
            }
        });
    }
}
