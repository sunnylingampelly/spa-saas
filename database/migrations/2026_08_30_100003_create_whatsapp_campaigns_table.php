<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // Restrict, not cascade — an approved template that a sent campaign already used
            // must never disappear out from under that campaign's send history.
            $table->foreignId('whatsapp_template_id')->constrained()->restrictOnDelete();

            $table->string('name');

            // Per-{{n}} config, e.g. [{"source":"customer_name"},{"source":"static","value":"20%"}].
            $table->json('variable_values');

            // Same segment shape as email_campaigns.audience_filter.
            $table->json('audience_filter');

            $table->enum('status', ['draft', 'sending', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();

            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamps();

            $table->index(['spa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaigns');
    }
};
