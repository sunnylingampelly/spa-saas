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
        Schema::table('customers', function (Blueprint $table) {
            // A durable preference, not a per-campaign thing — every future campaign's
            // audience query excludes an opted-out customer automatically.
            $table->boolean('marketing_opt_out')->default(false)->after('is_vip');
            $table->timestamp('marketing_opt_out_at')->nullable()->after('marketing_opt_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['marketing_opt_out', 'marketing_opt_out_at']);
        });
    }
};
