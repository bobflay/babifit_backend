<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_a_user_and_returns_tokens(): void
    {
        $response = $this->postJson('/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['accessToken', 'refreshToken', 'expiresIn']);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'initials' => 'JD',
        ]);

        // Password is stored hashed, not in plain text.
        $this->assertNotSame('secret123', User::firstWhere('email', 'jane@example.com')->password);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'secret123',
        ]);

        // Errors come back in this app's envelope: { error: { code, details } }.
        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['email']]]);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['name', 'email', 'password']]]);
    }
}
