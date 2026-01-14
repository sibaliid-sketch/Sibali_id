<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_as_student()
    {
        $studentData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            'birthdate' => '2005-05-15',
            'school_origin' => 'SMA Negeri 1',
            'education_level' => 'SMA',
            'terms' => '1',
        ];

        $response = $this->post('/register', $studentData);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'user_type' => 'student',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('students', [
            'id' => $user->id,
            'birthdate' => '2005-05-15',
            'school_origin' => 'SMA Negeri 1',
            'education_level' => 'SMA',
        ]);

        $this->assertDatabaseHas('sales_inquiries', [
            'email' => 'john@example.com',
            'source' => 'registration',
            'user_type' => 'student',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function user_can_register_as_parent()
    {
        $parentData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '081234567891',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'parent',
            'relationship' => 'mother',
            'terms' => '1',
        ];

        $response = $this->post('/register', $parentData);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '081234567891',
            'user_type' => 'parent',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertDatabaseHas('parents', [
            'id' => $user->id,
            'relationship' => 'mother',
        ]);

        $this->assertDatabaseHas('sales_inquiries', [
            'email' => 'jane@example.com',
            'source' => 'registration',
            'user_type' => 'parent',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('parent.dashboard'));
    }

    /** @test */
    public function registration_requires_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'phone' => '081234567892',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            'birthdate' => '2005-05-15',
            'education_level' => 'SMA',
            'terms' => '1',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_unique_phone()
    {
        User::factory()->create(['phone' => '081234567893']);

        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567893',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            'birthdate' => '2005-05-15',
            'education_level' => 'SMA',
            'terms' => '1',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_terms_acceptance()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '081234567894',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            'birthdate' => '2005-05-15',
            'education_level' => 'SMA',
            // 'terms' => '1', // Missing
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
    }

    /** @test */
    public function student_registration_requires_birthdate_and_education_level()
    {
        $data = [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'phone' => '081234567895',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            // 'birthdate' => '2005-05-15', // Missing
            'school_origin' => 'SMA Negeri 1',
            // 'education_level' => 'SMA', // Missing
            'terms' => '1',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors(['birthdate', 'education_level']);
        $this->assertGuest();
    }

    /** @test */
    public function parent_registration_requires_relationship()
    {
        $data = [
            'name' => 'Test Parent',
            'email' => 'parent@example.com',
            'phone' => '081234567896',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'parent',
            // 'relationship' => 'father', // Missing
            'terms' => '1',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors('relationship');
        $this->assertGuest();
    }
}
