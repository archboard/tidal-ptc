<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fills an install form with the credentials PowerSchool registered.
 * The channel is public because no user exists yet, so it's named by an HMAC of
 * the host that only the install page is given; other hosts can't guess it.
 */
class PowerSchoolPluginRegistered implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param  array{url: string, client_id: string, client_secret: string}  $sisConfig
     */
    public function __construct(public string $host, public array $sisConfig) {}

    public static function channelName(string $host): string
    {
        return 'install.'.hash_hmac('sha256', "install|{$host}", config('app.key'));
    }

    public function broadcastOn(): Channel
    {
        return new Channel(static::channelName($this->host));
    }

    /**
     * @return array{sisConfig: array{url: string, client_id: string, client_secret: string}}
     */
    public function broadcastWith(): array
    {
        return ['sisConfig' => $this->sisConfig];
    }
}
