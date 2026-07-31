<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();

            $table->string('customer_code');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('anniversary_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('occupation')->nullable();

            $table->text('medical_notes')->nullable();
            $table->text('allergy_notes')->nullable();
            $table->foreignId('preferred_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('preferred_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->unsignedInteger('reward_points')->default(0);
            $table->string('referral_code')->nullable();
            $table->foreignId('referred_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->json('tags')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->date('customer_since')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['spa_id', 'customer_code']);
            $table->unique(['spa_id', 'referral_code']);
            $table->index(['spa_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
