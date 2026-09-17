<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stores_first_and_last_name_separately(): void
    {
        Mail::fake();

        $response = $this->post(route('register.perform'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'username' => 'maria_santos',
            'email' => 'maria@example.com',
            'phone' => '09171234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertRedirect(route('auth.verify-email'));

        $this->assertDatabaseHas('users', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
        ]);

        $this->assertAuthenticatedAs(User::query()->where('email', 'maria@example.com')->firstOrFail());
        $this->assertDatabaseHas('roles', ['slug' => Role::SLUG_USER]);
    }
}