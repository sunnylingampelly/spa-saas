<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->enum('booking_type', ['walk_in', 'advance'])->default('advance');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('status', ['booked', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('booked');

            $table->text('notes')->nullable();
            $table->string('cancelled_reason')->nullable();

            $table->timestamp('email_reminder_sent_at')->nullable();
            $table->timestamp('sms_reminder_sent_at')->nullable();
            $table->timestamp('whatsapp_reminder_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['spa_id', 'starts_at']);
            $table->index(['employee_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
