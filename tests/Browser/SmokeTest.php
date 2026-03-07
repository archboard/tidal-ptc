<?php

beforeEach(function () {
    $this->tenant->update(['allow_password_auth' => true]);
    test()->setSchool();
});

it('can load the login page', function () {
    $page = visit('/login');

    $page->assertNoJavaScriptErrors();
});
