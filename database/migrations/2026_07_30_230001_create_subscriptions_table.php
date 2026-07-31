<?php

use App\Domain\Tenancy\Models\Spa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('plan_code', ['trial', 'monthly', 'lifetime'])->default('trial');
            $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled'])->default('trialing');
            $table->timestamp('starts_at');
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('razorpay_customer_id')->nullable();
            $table->string('razorpay_subscription_id')->nullable();

            $table->timestamps();
        });

        // Backfill a trialing subscription for any spa that already existed before this table did.
        $trialDays = config('subscriptions.trial_days', 14);

        Spa::withoutGlobalScopes()->whereDoesntHave('subscription')->get()->each(function (Spa $spa) use ($trialDays) {
            $spa->subscription()->create([
                'plan_code' => 'trial',
                'status' => 'trialing',
                'starts_at' => $spa->created_at ?? now(),
                'current_period_ends_at' => ($spa->created_at ?? now())->copy()->addDays($trialDays),
                'created_by_user_id' => $spa->owner_user_id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
