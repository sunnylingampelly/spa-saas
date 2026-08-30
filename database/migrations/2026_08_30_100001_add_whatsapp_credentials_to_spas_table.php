<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            // Per-spa WhatsApp Cloud API connection — mirrors the smtp_* columns above: plain
            // fields alongside one secret column, kept encrypted+hidden at the model level. The
            // spa brings their own Meta WhatsApp Business Account (phone_number_id + a permanent
            // System User access token); App\Domain\WhatsApp\Services\WhatsAppClient resolves
            // these explicitly, no ambient/shared credential. whatsapp_webhook_token doubles as
            // both this spa's webhook URL segment and the value Meta's verification handshake
            // checks — mirrors razorpay_webhook_token.
            $table->string('whatsapp_phone_number_id')->nullable()->after('mail_from_name');
            $table->string('whatsapp_business_account_id')->nullable()->after('whatsapp_phone_number_id');
            $table->text('whatsapp_access_token')->nullable()->after('whatsapp_business_account_id');
            $table->string('whatsapp_webhook_token')->nullable()->unique()->after('whatsapp_access_token');

            // Cached from Meta on connect/"Test Connection" so Settings can display the
            // connected number without an API call on every page load.
            $table->string('whatsapp_display_phone_number')->nullable()->after('whatsapp_webhook_token');
            $table->string('whatsapp_verified_name')->nullable()->after('whatsapp_display_phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('spas', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone_number_id', 'whatsapp_business_account_id', 'whatsapp_access_token',
                'whatsapp_webhook_token', 'whatsapp_display_phone_number', 'whatsapp_verified_name',
            ]);
        });
    }
};
