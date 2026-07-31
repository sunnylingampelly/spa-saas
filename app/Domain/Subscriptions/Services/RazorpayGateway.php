<?php

namespace App\Domain\Subscriptions\Services;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
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
                (string) config('services.razorpay.webhook_secret'),
            );

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    private function api(): Api
    {
        return new Api(config('services.razorpay.key_id'), config('services.razorpay.key_secret'));
    }
}
