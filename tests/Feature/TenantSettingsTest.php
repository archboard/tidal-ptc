<?php

beforeEach(function () {
    logIn();
});

it("can't be accessed without permission", function () {
    $this->get(route('settings.tenant.edit'))
        ->assertForbidden();
});

it('can be accessed with permission', function () {
    fullPermissions();

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->has('title')
            ->has('tenantForm')
            ->has('smtpForm')
            ->has('schools')
        )
        ->assertOk();
});

it('can update smtp settings', function () {
    fullPermissions();

    $data = [
        'host' => fake()->domainName(),
        'port' => fake()->randomElement([587, 465, 25, 2525]),
        'username' => fake()->email(),
        'password' => fake()->word(),
        'from_name' => fake()->name(),
        'from_address' => fake()->email(),
        'encryption' => fake()->randomElement(['tls', 'ssl']),
    ];

    $this->put(route('settings.tenant.smtp'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('settings.tenant.edit'));

    $this->tenant->refresh();
    $this->assertEquals($data, $this->tenant->smtp_config->toArray());
});

it('can update tenant settings', function () {
    fullPermissions();

    $data = [
        'name' => fake()->company(),
        'domain' => fake()->domainName(),
        'sis_provider' => fake()->randomElement(\App\Enums\Sis::cases())->value,
        'allow_password_auth' => fake()->boolean(),
        'allow_oidc_login' => fake()->boolean(),
    ];

    $this->put(route('settings.tenant.update'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('settings.tenant.edit'));

    $this->tenant->refresh();
    $this->assertEquals($data['name'], $this->tenant->name);
    $this->assertEquals($data['domain'], $this->tenant->domain);
    $this->assertEquals($data['sis_provider'], $this->tenant->sis_provider->value);
    $this->assertEquals($data['allow_password_auth'], $this->tenant->allow_password_auth);
    $this->assertEquals($data['allow_oidc_login'], $this->tenant->allow_oidc_login);
});
