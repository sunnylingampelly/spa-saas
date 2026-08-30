<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();

            // Null until Meta assigns one on submission.
            $table->string('meta_template_id')->nullable();

            // Meta's own naming rule: lowercase snake_case, unique per WABA + language.
            $table->string('name');
            $table->enum('category', ['marketing', 'utility']);
            $table->string('language')->default('en');

            // Text header only for v1 — image/video/document headers need Meta's separate
            // resumable media-upload API, deferred.
            $table->string('header_text')->nullable();
            $table->text('body_text');
            $table->string('footer_text')->nullable();

            // Up to 2 quick-reply buttons or 1 URL button for v1: [{"type":"QUICK_REPLY","text":"..."}]
            $table->json('buttons')->nullable();

            // Example values submitted alongside {{1}}, {{2}}... placeholders — Meta requires
            // these to review what will actually go in each variable.
            $table->json('variable_samples')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'paused'])->default('pending');
            $table->text('rejected_reason')->nullable();

            $table->timestamps();

            $table->index(['spa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
