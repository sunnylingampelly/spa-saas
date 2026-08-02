<?php

namespace Tests\Feature\Services;

use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\ServiceFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsSpreadsheetUploads;
use Tests\TestCase;

class ServiceImportExportTest extends TestCase
{
    use BuildsSpreadsheetUploads, RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
    }

    public function test_export_produces_a_valid_spreadsheet(): void
    {
        $spaId = Spa::withoutGlobalScopes()->where('owner_user_id', $this->owner->id)->firstOrFail()->id;
        ServiceFactory::new()->create(['spa_id' => $spaId, 'name' => 'Swedish Massage']);

        $response = $this->actingAs($this->owner)->get('/services/export');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('category', $sheet->getCell('A1')->getValue());
        $this->assertSame('name', $sheet->getCell('B1')->getValue());
        $this->assertSame('Swedish Massage', $sheet->getCell('B2')->getValue());
    }

    public function test_import_creates_a_new_category_by_name_when_it_does_not_exist_yet(): void
    {
        $file = $this->makeXlsxUpload(
            ['category', 'name', 'duration_minutes', 'price'],
            [['Body Treatments', 'Detox Wrap', '60', '1200']],
        );

        $response = $this->actingAs($this->owner)->post('/services/import', ['file' => $file]);

        $response->assertRedirect();
        $service = Service::with('category')->firstOrFail();
        $this->assertSame('Detox Wrap', $service->name);
        $this->assertSame('Body Treatments', $service->category->name);
        $this->assertSame(1, ServiceCategory::count());
    }

    public function test_import_reuses_an_existing_category_matched_case_insensitively(): void
    {
        $this->actingAs($this->owner)->post('/service-categories', ['name' => 'Facials']);
        $existing = ServiceCategory::firstOrFail();

        $file = $this->makeXlsxUpload(
            ['category', 'name', 'duration_minutes', 'price'],
            [['facials', 'Gold Facial', '45', '2000']], // deliberately different casing
        );

        $this->actingAs($this->owner)->post('/services/import', ['file' => $file]);

        $this->assertSame(1, ServiceCategory::count(), 'A new category should not have been created.');
        $service = Service::firstOrFail();
        $this->assertSame($existing->id, $service->service_category_id);
    }

    public function test_import_reports_row_errors_for_invalid_rows(): void
    {
        $file = $this->makeXlsxUpload(
            ['name', 'duration_minutes', 'price'],
            [['Missing duration', '', '500']],
        );

        $response = $this->actingAs($this->owner)->post('/services/import', ['file' => $file]);

        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1 && $errors[0]['row'] === 2);
        $this->assertSame(0, Service::count());
    }
}
