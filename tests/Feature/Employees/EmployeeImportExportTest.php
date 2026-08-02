<?php

namespace Tests\Feature\Employees;

use App\Domain\Employees\Models\Employee;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\EmployeeFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsSpreadsheetUploads;
use Tests\TestCase;

class EmployeeImportExportTest extends TestCase
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
        EmployeeFactory::new()->create(['spa_id' => $spaId, 'name' => 'Kavya Nair', 'designation' => 'Therapist']);

        $response = $this->actingAs($this->owner)->get('/employees/export');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('name', $sheet->getCell('A1')->getValue());
        $this->assertSame('Kavya Nair', $sheet->getCell('A2')->getValue());
    }

    public function test_import_creates_employees_from_valid_rows_including_numeric_looking_text_fields(): void
    {
        $file = $this->makeXlsxUpload(
            ['name', 'phone', 'designation', 'salary', 'experience_years'],
            [['Kavya Nair', '9876500000', 'Therapist', '25000', '3']],
        );

        $response = $this->actingAs($this->owner)->post('/employees/import', ['file' => $file]);

        $response->assertRedirect();
        $employee = Employee::firstOrFail();
        $this->assertSame('Kavya Nair', $employee->name);
        $this->assertSame('9876500000', $employee->phone);
        $this->assertSame(3, $employee->experience_years);
        $this->assertNotNull($employee->employee_code);
    }

    public function test_import_reports_row_errors_for_missing_required_fields(): void
    {
        $file = $this->makeXlsxUpload(
            ['name', 'designation'],
            [['', 'No name given']],
        );

        $response = $this->actingAs($this->owner)->post('/employees/import', ['file' => $file]);

        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1 && $errors[0]['row'] === 2);
        $this->assertSame(0, Employee::count());
    }
}
