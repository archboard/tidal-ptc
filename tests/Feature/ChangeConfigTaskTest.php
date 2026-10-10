<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;

// Queue workers and Octane keep one process across tenants, so a mailer built for one must not serve another
it('rebuilds the smtp mailer when switching tenants', function () {
    config()->set('app.self_hosted', true);
    $smtp = ['port' => 587, 'encryption' => 'tls', 'username' => 'u', 'password' => 'p', 'from_address' => 'a@example.test', 'from_name' => 'A'];
    $first = Tenant::factory()->create(['smtp_config' => [...$smtp, 'host' => 'mail.first.test']]);
    $second = Tenant::factory()->create(['smtp_config' => [...$smtp, 'host' => 'mail.second.test']]);

    $first->makeCurrent();
    $firstMailer = Mail::mailer();

    $second->makeCurrent();

    expect(Mail::mailer())->not->toBe($firstMailer)
        ->and(config('mail.mailers.smtp.host'))->toBe('mail.second.test');
});

it('only uses smtp for cloud tenants that opted into their own provider', function () {
    $this->asCloud();
    Tenant::forgetCurrent();
    config()->set('mail.default', 'postmark');
    $smtp = ['host' => 'mail.own.test', 'port' => 587, 'encryption' => 'tls', 'username' => 'u', 'password' => 'p', 'from_address' => 'a@example.test', 'from_name' => 'A'];
    $optedIn = Tenant::factory()->create(['smtp_config' => [...$smtp, 'custom' => true]]);
    $optedOut = Tenant::factory()->create(['smtp_config' => [...$smtp, 'custom' => false]]);

    $optedIn->makeCurrent();
    $optedInMailer = config('mail.default');
    $optedOut->makeCurrent();

    expect($optedInMailer)->toBe('smtp')
        ->and(config('mail.default'))->toBe('postmark');
});
