<?php

use App\Domain\Tenancy\Models\Spa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            $table->string('razorpay_key_id')->nullable()->after('currency');
            $table->text('razorpay_key_secret')->nullable()->after('razorpay_key_id');
            $table->text('razorpay_webhook_secret')->nullable()->after('razorpay_key_secret');
            $table->string('razorpay_webhook_token')->nullable()->unique()->after('razorpay_webhook_secret');
        });

        Spa::withoutGlobalScopes()->whereNull('razorpay_webhook_token')->each(
            fn (Spa $spa) => $spa->update(['razorpay_webhook_token' => Str::random(40)])
        );
    }

    public function down(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            $table->dropColumn(['razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret', 'razorpay_webhook_token']);
        });
    }
};
