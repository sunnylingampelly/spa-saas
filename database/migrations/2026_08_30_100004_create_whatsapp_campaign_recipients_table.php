<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // Snapshotted at send time — whatsapp_number, falling back to phone — so send
            // history survives the customer's number changing (or the customer being deleted)
            // later.
            $table->string('phone_number');

            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');

            // Meta's wamid — how a webhook status update finds its way back to this row.
            $table->string('meta_message_id')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['whatsapp_campaign_id', 'status']);
            $table->index('meta_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
    }
};
