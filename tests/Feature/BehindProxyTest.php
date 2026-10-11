<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehindProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_use_https_when_cloudflare_forwards_an_https_request(): void
    {
        $response = $this->get('/', ['X-Forwarded-Proto' => 'https']);

        $response->assertOk();
        $this->assertStringStartsWith('https://', url('/'));
        $this->assertStringStartsWith('https://', route('home'));
    }
}
