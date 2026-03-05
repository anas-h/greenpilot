<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_user_can_login(): void
    {
        $this->setupTestEnvironment();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user', 'permissions']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->setupTestEnvironment();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $this->setupTestEnvironment();
        $this->admin->update(['actif' => false]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $this->setupTestEnvironment();

        $response = $this->actingAsAdmin()
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('user.email', 'admin@test.com');
    }

    public function test_user_can_logout(): void
    {
        $this->setupTestEnvironment();

        $response = $this->actingAsAdmin()
            ->postJson('/api/auth/logout');

        $response->assertOk();
    }
}
