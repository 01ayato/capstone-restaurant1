<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
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
            'role_id' => Role::query()->where('slug', Role::SLUG_USER)->value('id'),
        ]);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('roles', ['slug' => Role::SLUG_USER]);

        $this->get(route('checkout.show'))
            ->assertRedirect(route('auth.verify-email'));

        $this->get(route('module.user_settings.index'))
            ->assertRedirect(route('auth.verify-email'));

        $this->get(route('auth.verify-email'))
            ->assertOk()
            ->assertSee('Verify Your Email');

        $code = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail()
            ->code;

        $this->post(route('auth.verify-email.perform'), ['code' => $code])
            ->assertRedirect(route('landing'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('checkout.show'))
            ->assertRedirect(route('menu.index'));
    }

    public function test_verified_users_can_access_authenticated_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertRedirect(route('menu.index'));
    }
}