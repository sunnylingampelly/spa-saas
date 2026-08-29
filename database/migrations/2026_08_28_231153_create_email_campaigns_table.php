<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->longText('body_html');

            // Segment type + params, e.g. {"type":"tag","tag":"vip-guest"} or {"type":"inactive_days","days":60}.
            $table->json('audience_filter');

            $table->enum('status', ['draft', 'sending', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();

            // Denormalized so the list/analytics views never need a COUNT(*) over recipients.
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);

            $table->timestamps();

            $table->index(['spa_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
