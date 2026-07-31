<?php

namespace Tests\Unit\Domain\Subscriptions;

use App\Domain\Subscriptions\Services\RazorpayGateway;
use Tests\TestCase;

class RazorpayGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.razorpay.key_id' => 'rzp_test_fake',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'test_webhook_secret',
        ]);
    }

    public function test_is_configured_reflects_whether_keys_are_set(): void
    {
        $this->assertTrue((new RazorpayGateway)->isConfigured());

        config(['services.razorpay.key_id' => null]);
        $this->assertFalse((new RazorpayGateway)->isConfigured());
    }

    public function test_verifies_a_correctly_signed_payment(): void
    {
        $orderId = 'order_test123';
        $paymentId = 'pay_test456';
        $signature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'test_secret_key');

        $this->assertTrue((new RazorpayGateway)->verifyPaymentSignature($orderId, $paymentId, $signature));
    }

    public function test_rejects_a_tampered_payment_signature(): void
    {
        $orderId = 'order_test123';
        $paymentId = 'pay_test456';
        $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'test_secret_key');

        // Attacker reuses a valid signature but swaps in a different payment id.
        $this->assertFalse((new RazorpayGateway)->verifyPaymentSignature($orderId, 'pay_attacker_swap', $validSignature));

        // Or simply forges an arbitrary signature.
        $this->assertFalse((new RazorpayGateway)->verifyPaymentSignature($orderId, $paymentId, 'not-a-real-signature'));
    }

    public function test_verifies_a_correctly_signed_webhook_payload(): void
    {
        $payload = json_encode(['event' => 'payment.captured']);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        $this->assertTrue((new RazorpayGateway)->verifyWebhookSignature($payload, $signature));
    }

    public function test_rejects_a_tampered_webhook_payload(): void
    {
        $payload = json_encode(['event' => 'payment.captured']);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        $tamperedPayload = json_encode(['event' => 'subscription.cancelled']);

        $this->assertFalse((new RazorpayGateway)->verifyWebhookSignature($tamperedPayload, $signature));
    }
}
