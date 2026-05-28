<?php

namespace Tests\Feature;

use Tests\TestCase;

class InertiaAuthRedirectTest extends TestCase
{
    public function test_unauthenticated_inertia_request_gets_full_page_login_redirect(): void
    {
        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('login'));

        $this->assertSame(route('admin.dashboard'), session('url.intended'));
    }
}