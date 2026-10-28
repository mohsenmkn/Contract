<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_root_returns_api_metadata(): void
    {
        $this->get('/')->assertOk()->assertJson(['name' => 'Budget Control API', 'version' => 'v1']);
    }

    public function test_missing_routes_return_json_even_without_accept_header(): void
    {
        $this->get('/api/v1/does-not-exist')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_guest_requests_return_json_instead_of_login_html(): void
    {
        $this->get('/api/v1/users')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }
}
