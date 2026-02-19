<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_user_resource_and_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'token',
            ])
            ->assertJsonPath('user.email', 'john@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_login_returns_user_resource_and_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'token',
            ])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_me_requires_auth_and_returns_user_resource(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
