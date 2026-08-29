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
        Schema::table('appointments', function (Blueprint $table) {
            // How the customer actually found the spa — distinct from booking_type (which just
            // says whether the slot was pre-scheduled or a walk-in queue entry). Lets revenue be
            // broken down by acquisition channel for ad-spend ROI.
            $table->string('lead_source')->default('walk_in')->after('booking_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('lead_source');
        });
    }
};
