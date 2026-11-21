<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class BrokerChallengeController extends Controller
{
    /**
     * Serves the registration token the broker fetches to prove control of this origin.
     */
    public function __invoke(): Response
    {
        $token = Cache::get('auth_broker.challenge');

        abort_unless(is_string($token), 404);

        return response($token, 200, ['Content-Type' => 'text/plain']);
    }
}
