<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Nullable, no default — the correct SAC code for a given service is a
            // tax/compliance decision for the spa's own GST practitioner, not
            // something this app should assume on their behalf.
            $table->string('hsn_sac_code', 10)->nullable()->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('hsn_sac_code');
        });
    }
};
