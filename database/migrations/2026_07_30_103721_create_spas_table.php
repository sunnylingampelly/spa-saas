<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_business_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('business_registration_number')->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('google_maps_link')->nullable();

            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('weekly_off_days')->nullable();
            $table->json('holiday_calendar')->nullable();

            $table->string('invoice_prefix')->default('INV');
            $table->string('invoice_footer_note')->nullable();
            $table->unsignedTinyInteger('financial_year_start_month')->default(4);
            $table->string('timezone')->default('Asia/Kolkata');
            $table->string('currency', 3)->default('INR');

            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial');
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spas');
    }
};
