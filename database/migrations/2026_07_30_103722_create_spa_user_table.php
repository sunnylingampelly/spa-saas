<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spa_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_label')->default('owner');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['spa_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spa_user');
    }
};
