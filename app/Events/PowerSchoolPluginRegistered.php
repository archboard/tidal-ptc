<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fills a self-hosted install form with the credentials PowerSchool registered.
 * The channel is public because no user exists yet; the same values are already
 * served to anyone on /install until the first user is created.
 */
class PowerSchoolPluginRegistered implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param  array{url: string, client_id: string, client_secret: string}  $sisConfig
     */
    public function __construct(public array $sisConfig) {}

    public function broadcastOn(): Channel
    {
        return new Channel('install');
    }
}
