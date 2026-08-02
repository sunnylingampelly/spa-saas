<?php

namespace Tests\Feature\Billing;

use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class InvoiceExportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', [
            'name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000, 'gst_rate' => 18,
        ]);
        $service = Service::firstOrFail();

        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
    }

    public function test_export_produces_a_valid_spreadsheet_with_the_invoice_totals(): void
    {
        $response = $this->actingAs($this->owner)->get('/invoices/export');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('invoice_number', $sheet->getCell('A1')->getValue());
        $this->assertStringStartsWith('INV/', $sheet->getCell('A2')->getValue());
        $this->assertSame('Anjali Mehta', $sheet->getCell('C2')->getValue());
        $this->assertEqualsWithDelta(2360.0, (float) $sheet->getCell('K2')->getValue(), 0.01);
    }

    public function test_export_honors_the_status_filter(): void
    {
        $response = $this->actingAs($this->owner)->get('/invoices/export?status=paid');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        // The seeded invoice is "unpaid" — filtering to "paid" must leave only the header row.
        $this->assertNull($sheet->getCell('A2')->getValue());
    }
}
