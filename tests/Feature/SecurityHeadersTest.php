<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_every_response_carries_baseline_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_content_security_policy_is_only_sent_in_production(): void
    {
        $this->get('/login')->assertHeaderMissing('Content-Security-Policy');

        app()->detectEnvironment(fn () => 'production');
        $this->get('/login')->assertHeader('Content-Security-Policy');
    }
}
