<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['payment', 'refund'])->default('payment');
            $table->enum('method', ['cash', 'upi', 'card', 'wallet', 'gift_voucher', 'bank_transfer']);
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('paid_at');

            $table->timestamps();

            $table->index(['spa_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
