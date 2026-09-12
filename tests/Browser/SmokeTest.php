<?php

beforeEach(function () {
    $this->tenant->update(['allow_password_auth' => true]);
    setSchool();
    $this->withoutExceptionHandling();
});

it('loads guest pages without smoke', function () {
    visit(['/login', '/forgot-password'])->assertNoSmoke();
});

it('loads authenticated pages without smoke', function () {
    logIn();
    fullPermissions();

    visit([
        '/',
        '/activity',
        '/batches',
        '/batches/create',
        '/courses',
        '/sections',
        '/select-school',
        '/settings/personal/edit',
        '/settings/school/edit',
        '/settings/tenant/edit',
        '/students',
        '/teachers',
        '/time-slots',
        '/time-slots/create',
        '/translator-profiles',
        '/translators',
        '/users',
    ])->assertNoSmoke();
});
