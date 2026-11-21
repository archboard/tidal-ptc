<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthBrokerRegistration
{
    /**
     * Proves control of this origin to the auth broker.
     *
     * @return string|null The user-facing error, or null when registered.
     */
    public function register(string $license, string $origin): ?string
    {
        $token = Str::random(48);
        Cache::put('auth_broker.challenge', $token, now()->addMinutes(10));

        try {
            $response = Http::acceptJson()->timeout(15)->post(
                config('services.auth_broker.url').'/api/apps/'.config('services.auth_broker.app').'/registrations',
                ['license' => $license, 'origin' => $origin, 'token' => $token],
            );
        } catch (ConnectionException) {
            return __('ClassLink registration failed: :reason', ['reason' => __('could not reach the auth broker')]);
        } finally {
            Cache::forget('auth_broker.challenge');
        }

        if ($response->successful()) {
            return null;
        }

        return __('ClassLink registration failed: :reason', ['reason' => Str::limit(trim($response->body()), 200)]);
    }
}
