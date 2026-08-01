<?php

namespace Tests\Unit\Domain\Subscriptions;

use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Domain\Tenancy\Models\Spa;
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

    public function test_platform_is_configured_reflects_whether_keys_are_set(): void
    {
        $this->assertTrue(RazorpayGateway::platform()->isConfigured());

        config(['services.razorpay.key_id' => null]);
        $this->assertFalse(RazorpayGateway::platform()->isConfigured());
    }

    public function test_for_spa_reads_the_spas_own_credentials_not_the_platforms(): void
    {
        $spa = new Spa([
            'razorpay_key_id' => 'rzp_spa_own_key',
            'razorpay_key_secret' => 'spa_own_secret',
        ]);

        $this->assertTrue(RazorpayGateway::forSpa($spa)->isConfigured());
    }

    public function test_for_spa_is_not_configured_when_the_spa_has_no_credentials_of_its_own(): void
    {
        $spa = new Spa;

        $this->assertFalse(RazorpayGateway::forSpa($spa)->isConfigured());
    }

    public function test_verifies_a_correctly_signed_payment(): void
    {
        $orderId = 'order_test123';
        $paymentId = 'pay_test456';
        $signature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'test_secret_key');

        $this->assertTrue(RazorpayGateway::platform()->verifyPaymentSignature($orderId, $paymentId, $signature));
    }

    public function test_rejects_a_tampered_payment_signature(): void
    {
        $orderId = 'order_test123';
        $paymentId = 'pay_test456';
        $validSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", 'test_secret_key');

        // Attacker reuses a valid signature but swaps in a different payment id.
        $this->assertFalse(RazorpayGateway::platform()->verifyPaymentSignature($orderId, 'pay_attacker_swap', $validSignature));

        // Or simply forges an arbitrary signature.
        $this->assertFalse(RazorpayGateway::platform()->verifyPaymentSignature($orderId, $paymentId, 'not-a-real-signature'));
    }

    public function test_verifies_a_correctly_signed_webhook_payload(): void
    {
        $payload = json_encode(['event' => 'payment.captured']);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        $this->assertTrue(RazorpayGateway::platform()->verifyWebhookSignature($payload, $signature));
    }

    public function test_rejects_a_tampered_webhook_payload(): void
    {
        $payload = json_encode(['event' => 'payment.captured']);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        $tamperedPayload = json_encode(['event' => 'subscription.cancelled']);

        $this->assertFalse(RazorpayGateway::platform()->verifyWebhookSignature($tamperedPayload, $signature));
    }

    public function test_a_spas_webhook_signature_does_not_verify_against_another_spas_secret(): void
    {
        $spaA = new Spa(['razorpay_webhook_secret' => 'spa_a_secret']);
        $spaB = new Spa(['razorpay_webhook_secret' => 'spa_b_secret']);

        $payload = json_encode(['event' => 'payment.captured']);
        $signatureForSpaA = hash_hmac('sha256', $payload, 'spa_a_secret');

        $this->assertTrue(RazorpayGateway::forSpa($spaA)->verifyWebhookSignature($payload, $signatureForSpaA));
        $this->assertFalse(RazorpayGateway::forSpa($spaB)->verifyWebhookSignature($payload, $signatureForSpaA));
    }
}
