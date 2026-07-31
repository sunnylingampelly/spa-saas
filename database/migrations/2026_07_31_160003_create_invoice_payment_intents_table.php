<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->decimal('amount', 12, 2);

            $table->string('razorpay_order_id');
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['spa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_intents');
    }
};
