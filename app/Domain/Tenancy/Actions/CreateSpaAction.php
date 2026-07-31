<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Subscriptions\Actions\StartTrialSubscriptionAction;
use App\Domain\Tenancy\DTOs\CreateSpaData;
use App\Domain\Tenancy\Models\Spa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSpaAction
{
    public function __construct(private readonly StartTrialSubscriptionAction $startTrialSubscription) {}

    public function execute(CreateSpaData $data): Spa
    {
        return DB::transaction(function () use ($data) {
            $spa = Spa::create([
                'owner_user_id' => $data->ownerUserId,
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->name),
                'phone' => $data->phone,
                'email' => $data->email,
                'gst_number' => $data->gstNumber,
                'city' => $data->city,
                'state' => $data->state,
                'onboarding_completed_at' => now(),
            ]);

            $spa->users()->attach($data->ownerUserId, [
                'role_label' => 'owner',
                'is_default' => true,
            ]);

            $this->startTrialSubscription->execute($spa, $data->ownerUserId);

            return $spa;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Spa::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
