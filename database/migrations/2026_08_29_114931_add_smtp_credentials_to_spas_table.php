<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            // Per-spa SMTP — mirrors the razorpay_* columns above: plain fields alongside
            // one secret column, kept encrypted+hidden at the model level. When left empty,
            // App\Domain\Tenancy\Services\SpaMailer falls back to the platform's own mailer.
            $table->string('smtp_host')->nullable()->after('razorpay_webhook_token');
            $table->string('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->nullable()->after('smtp_password');
            $table->string('mail_from_address')->nullable()->after('smtp_encryption');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
                'smtp_encryption', 'mail_from_address', 'mail_from_name',
            ]);
        });
    }
};
