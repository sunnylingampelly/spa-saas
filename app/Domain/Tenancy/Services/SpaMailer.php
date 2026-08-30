<?php

namespace App\Domain\Tenancy\Services;

use App\Domain\Tenancy\Models\Spa;

/**
 * Resolves which registered Laravel mailer a spa's outbound mail should go through — the
 * mail equivalent of RazorpayGateway::forSpa(), but shaped around what Laravel's Mail system
 * actually needs (a mailer *name*, since that's the unit `Mail::mailer()` operates on) rather
 * than a set of constructor args.
 *
 * A distinct mailer name per spa id (rather than one shared name) sidesteps Laravel's
 * MailManager per-name mailer caching entirely: there is no risk of one spa's job reusing a
 * previous spa's cached transport/credentials inside a long-running queue worker, and no
 * global config (like mail.default) is ever mutated or needs resetting between jobs.
 */
class SpaMailer
{
    public static function mailerFor(Spa $spa): string
    {
        if (! filled($spa->smtp_host)) {
            // No per-spa SMTP configured — fall back to the platform's own mailer, exactly
            // like every other kind of email sent by this app today.
            return env('MAIL_MAILER', 'log');
        }

        $name = "spa_smtp_{$spa->id}";

        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            'host' => $spa->smtp_host,
            'port' => $spa->smtp_port,
            'username' => $spa->smtp_username,
            'password' => $spa->smtp_password,
            'encryption' => $spa->smtp_encryption ?: null,
        ]]);

        return $name;
    }

    public static function isConfigured(Spa $spa): bool
    {
        return filled($spa->smtp_host);
    }
}
