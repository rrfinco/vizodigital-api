<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiOnlyModeTest extends TestCase
{
    public function test_api_only_blocks_portal_web_but_allows_api(): void
    {
        config([
            'portal.api_only' => true,
            'portal.public_url' => 'https://docs.rrfinco.com',
            'app.url' => 'https://uat-api.rrfinco.com',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertJsonPath('mode', 'api_only')
            ->assertJsonPath('portal', 'https://docs.rrfinco.com');

        $this->get('/docs')->assertNotFound()->assertJsonPath('portal', 'https://docs.rrfinco.com');
        $this->get('/login')->assertNotFound();
        $this->get('/register')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $this->get('/user/login')->assertNotFound();

        $this->postJson('/api/v1/auth/token', [])
            ->assertStatus(422);
    }

    public function test_portal_web_works_when_api_only_disabled(): void
    {
        config(['portal.api_only' => false]);

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }
}
