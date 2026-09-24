<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpsTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void
    {
        config(['app.force_https' => false]);

        parent::tearDown();
    }

    public function test_http_requests_are_redirected_to_https_when_enabled(): void
    {
        config(['app.force_https' => true]);

        $this->get('http://localhost/')
            ->assertStatus(301)
            ->assertRedirect('https://localhost');
    }

    public function test_health_check_is_not_blocked_by_https_redirect(): void
    {
        config(['app.force_https' => true]);

        $this->get('/up')->assertOk();
    }

    public function test_secure_responses_include_hsts_header(): void
    {
        config(['app.force_https' => true]);

        $this->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
