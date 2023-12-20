<?php

namespace Tests\Feature;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_without_passwords_cant_login()
    {
        $user = $this->seedUser();
        $this->tenant->update(['allow_password_auth' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertNotFound();
    }

    public function test_login_screen_can_be_rendered()
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewHas('title')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->has('title')
                ->has('status')
            );
    }

    public function test_login_screen_can_be_rendered_when_passwords_are_disabled()
    {
        $this->tenant->update(['allow_password_auth' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertViewHas('title')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->has('title')
                ->has('status')
            );
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = $this->seedUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = $this->seedUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
