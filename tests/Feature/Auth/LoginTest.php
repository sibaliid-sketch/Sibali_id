<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_email()
    {
        // Create a dummy user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Attempt login
        $response = $this->post(route('enhanced.login'), [
            'identifier' => 'test@example.com',
            'password' => 'password',
        ]);

        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);

        // Assert redirect to intended route
        $response->assertRedirect();
    }

    /** @test */
    public function user_can_login_with_phone()
    {
        // Create a dummy user
        $user = User::factory()->create([
            'phone' => '081234567890',
            'password' => bcrypt('password'),
        ]);

        // Attempt login
        $response = $this->post(route('enhanced.login'), [
            'identifier' => '081234567890',
            'password' => 'password',
        ]);

        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);

        // Assert redirect
        $response->assertRedirect();
    }

    /** @test */
    public function login_fails_with_wrong_credentials()
    {
        // Create a dummy user
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Attempt login with wrong password
        $response = $this->post(route('enhanced.login'), [
            'identifier' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert user is not authenticated
        $this->assertGuest();

        // Assert validation error
        $response->assertSessionHasErrors('identifier');
    }

    /** @test */
    public function login_requires_identifier_and_password()
    {
        // Attempt login without credentials
        $response = $this->post(route('enhanced.login'), []);

        // Assert validation errors
        $response->assertSessionHasErrors(['identifier', 'password']);
    }
}
