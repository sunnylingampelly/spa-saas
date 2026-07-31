<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\DTOs\CreateCustomerData;
use App\Domain\Customers\Models\Customer;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCustomerAction
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function execute(CreateCustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $referredBy = $data->referralCode
                ? Customer::where('referral_code', $data->referralCode)->first()
                : null;

            return Customer::create([
                'customer_code' => $this->nextCustomerCode(),
                'name' => $data->name,
                'phone' => $data->phone,
                'whatsapp_number' => $data->whatsappNumber,
                'date_of_birth' => $data->dateOfBirth,
                'anniversary_date' => $data->anniversaryDate,
                'gender' => $data->gender,
                'email' => $data->email,
                'city' => $data->city,
                'state' => $data->state,
                'occupation' => $data->occupation,
                'medical_notes' => $data->medicalNotes,
                'allergy_notes' => $data->allergyNotes,
                'preferred_service_id' => $data->preferredServiceId,
                'preferred_employee_id' => $data->preferredEmployeeId,
                'tags' => $data->tags,
                'is_vip' => $data->isVip,
                'referred_by_customer_id' => $referredBy?->id,
                'referral_code' => $this->uniqueReferralCode(),
                'customer_since' => now()->toDateString(),
            ]);
        });
    }

    private function nextCustomerCode(): string
    {
        $spaId = $this->tenantContext->getCurrentSpaId();

        $count = Customer::withoutGlobalScopes()->where('spa_id', $spaId)->withTrashed()->count();

        do {
            $count++;
            $code = sprintf('CUST-%04d', $count);
        } while (Customer::withoutGlobalScopes()->withTrashed()->where('spa_id', $spaId)->where('customer_code', $code)->exists());

        return $code;
    }

    private function uniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Customer::withoutGlobalScopes()->where('referral_code', $code)->exists());

        return $code;
    }
}
