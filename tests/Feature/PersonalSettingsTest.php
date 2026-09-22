<?php

use App\Enums\NotificationEvent;
use App\Enums\UserType;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it('has a personal settings page', function () {
    $this->get(route('settings.personal.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/Personal')
            ->has('hasPassword')
        );
});

it('can update personal settings', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => fake()->email(),
        'timezone' => fake()->timezone(),
        'is_24h' => fake()->boolean(),
        'locale' => 'ja',
    ];

    $this->put(route('settings.personal.update'), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->user->refresh();
    $this->assertEquals($data['first_name'], $this->user->first_name);
    $this->assertEquals($data['last_name'], $this->user->last_name);
    $this->assertEquals($data['email'], $this->user->email);
    $this->assertEquals($data['timezone'], $this->user->timezone);
    $this->assertEquals($data['is_24h'], $this->user->is_24h);
    $this->assertEquals('ja', $this->user->locale);
});

it('defaults notifications to on and honors opt-outs', function () {
    $this->user->update(['user_type' => UserType::guardian]);

    expect($this->user->wantsNotification(NotificationEvent::slot_booked))->toBeTrue();

    $this->put('/settings/personal/notifications', ['slot_booked' => false, 'slot_reminder' => true])
        ->assertRedirect();

    $this->user->refresh();
    expect($this->user->wantsNotification(NotificationEvent::slot_booked))->toBeFalse()
        ->and($this->user->wantsNotification(NotificationEvent::slot_reminder))->toBeTrue()
        ->and($this->user->wantsNotification(NotificationEvent::slot_cancelled))->toBeTrue();

    $this->user->update(['user_type' => UserType::student]);
    expect($this->user->wantsNotification(NotificationEvent::slot_reminder))->toBeFalse();
});

it('applies the user locale to requests', function () {
    $this->user->update(['locale' => 'ja']);

    $this->get(route('settings.personal.edit'))->assertOk();

    expect(app()->getLocale())->toBe('ja');
});

it('can switch locale from the nav', function () {
    $this->put(route('settings.locale.update'), ['locale' => 'ja'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->user->refresh()->locale)->toBe('ja');
});

it('rejects an unsupported locale', function () {
    $this->put(route('settings.locale.update'), ['locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('exposes the user locale as their notification locale preference', function () {
    $this->user->update(['locale' => 'ja']);

    expect($this->user)->toBeInstanceOf(HasLocalePreference::class)
        ->and($this->user->preferredLocale())->toBe('ja');
});

it('renders the page right-to-left in Arabic', function () {
    $this->user->update(['locale' => 'ar']);

    $this->get(route('settings.personal.edit'))
        ->assertOk()
        ->assertSee('dir="rtl"', false);
});

it('renders the page left-to-right in other locales', function () {
    $this->user->update(['locale' => 'ja']);

    $this->get(route('settings.personal.edit'))
        ->assertOk()
        ->assertSee('dir="ltr"', false);
});
