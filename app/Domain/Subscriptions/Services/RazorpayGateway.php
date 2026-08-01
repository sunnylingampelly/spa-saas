<?php

namespace App\Domain\Subscriptions\Services;

use App\Domain\Tenancy\Models\Spa;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * Two distinct Razorpay accounts exist in this app: the platform's own (subscription
 * revenue) and each spa's own (their customers' invoice payments). Callers must be
 * explicit about which one they mean via platform()/forSpa() — there is no ambient
 * "default" credential source.
 */
class RazorpayGateway
{
    public function __construct(
        private readonly ?string $keyId = null,
        private readonly ?string $keySecret = null,
        private readonly ?string $webhookSecret = null,
    ) {}

    public static function platform(): self
    {
        return new self(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
            config('services.razorpay.webhook_secret'),
        );
    }

    public static function forSpa(Spa $spa): self
    {
        return new self(
            $spa->razorpay_key_id,
            $spa->razorpay_key_secret,
            $spa->razorpay_webhook_secret,
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->keyId) && filled($this->keySecret);
    }

    /**
     * @return array{id: string, amount: int, currency: string}
     */
    public function createOrder(int $amountInPaise, string $receipt): array
    {
        $order = $this->api()->order->create([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => $receipt,
        ]);

        return $order->toArray();
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $this->api()->utility->verifyWebhookSignature(
                $payload,
                $signature,
                (string) $this->webhookSecret,
            );

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    private function api(): Api
    {
        return new Api($this->keyId, $this->keySecret);
    }
}
