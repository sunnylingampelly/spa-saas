<?php

namespace Tests\Feature\Appointments;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Real-world case: "Radiance Day Spa" wants to know whether the money spent on Google/Meta
 * ads is actually paying off, versus what would have come in anyway from walk-ins and
 * referrals — this locks down that the revenue-by-source report adds up correctly and never
 * silently drops revenue that isn't tied to any appointment at all.
 */
class LeadSourceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 0]);
        $this->service = Service::firstOrFail();
    }

    private function bookAndPay(string $leadSource, int $startsAtOffsetMinutes): void
    {
        $startsAt = now()->addDay()->setTime(10, 0)->addMinutes($startsAtOffsetMinutes);

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'lead_source' => $leadSource,
            'starts_at' => $startsAt->format('Y-m-d H:i'),
        ])->assertRedirect();

        $appointment = Appointment::where('lead_source', $leadSource)->latest()->firstOrFail();

        $this->actingAs($this->owner)->post('/invoices', [
            'appointment_id' => $appointment->id,
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ])->assertRedirect();

        $invoice = $appointment->fresh()->invoice;

        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1000]],
        ])->assertRedirect();
    }

    public function test_revenue_is_grouped_by_lead_source_and_reconciles_to_the_spas_total(): void
    {
        $this->bookAndPay('google_ads', 0);
        $this->bookAndPay('meta_ads', 90);
        $this->bookAndPay('walk_in', 180);

        // A direct bill with no appointment behind it at all — must still be counted, just
        // bucketed separately, so the report's total never quietly disagrees with reality.
        $this->actingAs($this->owner)->post('/invoices', [
            'guest_name' => 'Walk-in Guest',
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ])->assertRedirect();
        $directInvoice = Invoice::whereNull('appointment_id')->firstOrFail();
        $this->actingAs($this->owner)->post("/invoices/{$directInvoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1000]],
        ]);

        $response = $this->actingAs($this->owner)->get('/reports/lead-sources?from=2020-01-01&to=2030-01-01');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $rows = collect($page->toArray()['props']['rows'])->keyBy(fn ($row) => $row['lead_source'] ?? 'direct');

            $page->where('totalRevenue', 4000);
            $this->assertSame(1000.0, (float) $rows['google_ads']['revenue']);
            $this->assertSame(1000.0, (float) $rows['meta_ads']['revenue']);
            $this->assertSame(1000.0, (float) $rows['walk_in']['revenue']);
            $this->assertSame(1000.0, (float) $rows['direct']['revenue']);

            return $page;
        });
    }

    public function test_unpaid_invoices_are_excluded_from_the_report(): void
    {
        $startsAt = now()->addDay()->setTime(11, 0);

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'lead_source' => 'google_ads',
            'starts_at' => $startsAt->format('Y-m-d H:i'),
        ]);
        $appointment = Appointment::firstOrFail();

        // Billed but never paid.
        $this->actingAs($this->owner)->post('/invoices', [
            'appointment_id' => $appointment->id,
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);

        $response = $this->actingAs($this->owner)->get('/reports/lead-sources?from=2020-01-01&to=2030-01-01');

        $response->assertInertia(fn ($page) => $page->where('totalRevenue', 0)->where('rows', []));
    }
}
