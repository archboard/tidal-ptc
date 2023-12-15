<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_without_passwords_can_reset()
    {
        $this->tenant->update(['allow_password_auth' => false]);

        $this->get('/forgot-password')
            ->assertNotFound();
    }

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertViewHas('title')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ForgotPassword')
                ->has('title')
                ->has('status')
            );
    }

    public function test_reset_password_link_can_be_requested_and_reset_successfully()
    {
        Notification::fake();

        $user = $this->seedUser();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token)
                ->assertOk()
                ->assertViewHas('title')
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Auth/ResetPassword')
                    ->has('title')
                    ->has('email')
                    ->where('token', $notification->token)
                );

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            return true;
        });
    }
}
