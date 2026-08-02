<?php

namespace Tests\Feature\Customers;

use App\Domain\Customers\Models\Customer;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsSpreadsheetUploads;
use Tests\TestCase;

class CustomerImportExportTest extends TestCase
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

    public function test_export_produces_a_valid_spreadsheet_honoring_the_search_filter(): void
    {
        $spaId = Spa::withoutGlobalScopes()->where('owner_user_id', $this->owner->id)->firstOrFail()->id;
        CustomerFactory::new()->create(['spa_id' => $spaId, 'name' => 'Anjali Mehta', 'phone' => '9876500000']);
        CustomerFactory::new()->create(['spa_id' => $spaId, 'name' => 'Rohan Gupta', 'phone' => '9876500001']);

        $response = $this->actingAs($this->owner)->get('/customers/export?search=Anjali');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('name', $sheet->getCell('A1')->getValue());
        $this->assertSame('Anjali Mehta', $sheet->getCell('A2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue()); // Rohan filtered out.
    }

    public function test_import_creates_customers_from_valid_rows(): void
    {
        $file = $this->makeXlsxUpload(
            ['name', 'phone', 'gender', 'is_vip'],
            [
                ['Anjali Mehta', '9876500000', 'female', 'yes'],
                ['Rohan Gupta', '9876500001', 'male', ''],
            ],
        );

        $response = $this->actingAs($this->owner)->post('/customers/import', ['file' => $file]);

        $response->assertRedirect();
        $this->assertSame(2, Customer::count());

        $anjali = Customer::where('name', 'Anjali Mehta')->firstOrFail();
        $this->assertSame('9876500000', $anjali->phone);
        $this->assertTrue((bool) $anjali->is_vip);
        $this->assertNotNull($anjali->customer_code);
    }

    public function test_import_skips_invalid_rows_and_reports_the_row_number_and_reason(): void
    {
        $file = $this->makeXlsxUpload(
            ['name', 'phone', 'email'],
            [
                ['Valid Customer', '9876500000', ''],
                ['', '9876500001', 'not-an-email'], // row 3: missing name + bad email
            ],
        );

        $response = $this->actingAs($this->owner)->post('/customers/import', ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('import_errors', function ($errors) {
            return count($errors) === 1 && $errors[0]['row'] === 3;
        });
        $this->assertSame(1, Customer::count());
    }

    public function test_import_never_creates_customers_in_another_spas_tenant(): void
    {
        $otherOwner = User::factory()->create();
        $otherOwner->assignRole('spa_owner');
        $this->actingAs($otherOwner)->post('/onboarding/create-spa', ['name' => 'Other Spa', 'phone' => '9999999999', 'state' => 'Karnataka']);
        $otherSpa = Spa::withoutGlobalScopes()->where('name', 'Other Spa')->firstOrFail();

        $file = $this->makeXlsxUpload(['name'], [['Tenant Isolation Check']]);
        $this->actingAs($this->owner)->post('/customers/import', ['file' => $file]);

        $created = Customer::where('name', 'Tenant Isolation Check')->firstOrFail();
        $myOwnerSpa = Spa::withoutGlobalScopes()->where('owner_user_id', $this->owner->id)->firstOrFail();

        $this->assertSame($myOwnerSpa->id, $created->spa_id);
        $this->assertNotSame($otherSpa->id, $created->spa_id);
    }
}
