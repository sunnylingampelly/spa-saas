<?php

namespace Tests\Feature\Expenses;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\ExpenseFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsSpreadsheetUploads;
use Tests\TestCase;

class ExpenseImportExportTest extends TestCase
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
        ExpenseFactory::new()->create(['spa_id' => $spaId, 'category' => 'Rent', 'amount' => 15000]);

        $response = $this->actingAs($this->owner)->get('/expenses/export');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('category', $sheet->getCell('A1')->getValue());
        $this->assertSame('Rent', $sheet->getCell('A2')->getValue());
    }

    public function test_import_creates_expenses_from_valid_rows_and_attributes_them_to_the_importing_user(): void
    {
        $file = $this->makeXlsxUpload(
            ['category', 'amount', 'expense_date', 'notes'],
            [['Electricity', '2500', '2026-07-01', 'July bill']],
        );

        $response = $this->actingAs($this->owner)->post('/expenses/import', ['file' => $file]);

        $response->assertRedirect();
        $expense = Expense::firstOrFail();
        $this->assertSame('Electricity', $expense->category);
        $this->assertSame($this->owner->id, $expense->created_by_user_id);
    }

    public function test_import_rejects_a_category_outside_the_fixed_list(): void
    {
        $file = $this->makeXlsxUpload(
            ['category', 'amount', 'expense_date'],
            [['NotARealCategory', '100', '2026-07-01']],
        );

        $response = $this->actingAs($this->owner)->post('/expenses/import', ['file' => $file]);

        $response->assertSessionHas('import_errors', fn ($errors) => count($errors) === 1 && $errors[0]['row'] === 2);
        $this->assertSame(0, Expense::count());
    }
}
