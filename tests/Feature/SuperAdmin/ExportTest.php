<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->admin->assignRole('super_admin');

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
    }

    public function test_spas_export_produces_a_valid_spreadsheet_honoring_search(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/spas/export?search=Radiance');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('name', $sheet->getCell('A1')->getValue());
        $this->assertSame('Radiance Day Spa', $sheet->getCell('A2')->getValue());
    }

    public function test_spas_export_search_excludes_non_matching_spas(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/spas/export?search=NoSuchSpaName');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertNull($sheet->getCell('A2')->getValue());
    }

    public function test_payments_export_produces_a_valid_spreadsheet_honoring_status_filter(): void
    {
        $spa = Spa::withoutGlobalScopes()->firstOrFail();
        SubscriptionPayment::withoutGlobalScopes()->create([
            'spa_id' => $spa->id,
            'subscription_id' => $spa->subscription->id,
            'plan_code' => 'monthly',
            'method' => 'manual',
            'status' => 'pending',
            'amount' => 1499,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/payments/export?status=pending');
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('spa', $sheet->getCell('A1')->getValue());
        $this->assertSame('Radiance Day Spa', $sheet->getCell('A2')->getValue());
        $this->assertSame('monthly', $sheet->getCell('B2')->getValue());

        $paidOnly = $this->actingAs($this->admin)->get('/admin/payments/export?status=paid');
        $paidPath = tempnam(sys_get_temp_dir(), 'export_test_').'.xlsx';
        file_put_contents($paidPath, $paidOnly->streamedContent());
        $paidSheet = IOFactory::load($paidPath)->getActiveSheet();
        $this->assertNull($paidSheet->getCell('A2')->getValue());
    }
}
