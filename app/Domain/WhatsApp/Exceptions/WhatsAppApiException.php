<?php

namespace App\Domain\WhatsApp\Exceptions;

use Exception;

/**
 * Carries Meta's own error message through untouched — surfacing the real reason a send/create/
 * fetch call failed (a bad token, an unapproved template, a malformed phone number) is far more
 * useful than a generic "WhatsApp request failed," both to us debugging and to the spa owner
 * reading a flashed error message.
 */
class WhatsAppApiException extends Exception
{
    public static function fromResponseBody(array $body, int $status): self
    {
        $message = $body['error']['message'] ?? "WhatsApp API request failed with status {$status}.";
        $details = $body['error']['error_data']['details'] ?? null;

        return new self($details ? "{$message} ({$details})" : $message);
    }
}
