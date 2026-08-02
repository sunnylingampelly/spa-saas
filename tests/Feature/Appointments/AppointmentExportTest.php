<?php

namespace Tests\Feature\Appointments;

use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AppointmentExportTest extends TestCase
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

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/employees', ['name' => 'Priya Therapist']);
        $employee = Employee::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000]);
        $service = Service::firstOrFail();

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'starts_at' => '2026-08-10 10:00',
        ]);
    }

    public function test_export_produces_a_valid_spreadsheet_for_the_selected_day_only(): void
    {
        $response = $this->actingAs($this->owner)->get('/appointments/export?date=2026-08-10');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('time', $sheet->getCell('A1')->getValue());
        $this->assertSame('10:00', $sheet->getCell('A2')->getValue());
        $this->assertSame('Anjali Mehta', $sheet->getCell('B2')->getValue());
        $this->assertSame('Priya Therapist', $sheet->getCell('D2')->getValue());
    }

    public function test_export_for_a_different_day_returns_no_rows(): void
    {
        $response = $this->actingAs($this->owner)->get('/appointments/export?date=2026-08-11');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertNull($sheet->getCell('A2')->getValue());
    }
}
