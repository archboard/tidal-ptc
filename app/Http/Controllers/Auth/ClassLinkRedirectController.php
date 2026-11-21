<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuthBrokerRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassLinkRedirectController extends Controller
{
    /**
     * Sends the browser to the auth broker (self-hosted connects re-register the origin first); the purpose comes from the route default.
     */
    public function __invoke(Request $request, AuthBrokerRegistration $registration, string $purpose): RedirectResponse
    {
        $broker = config('services.auth_broker.url');

        abort_if(empty($broker), 404);

        if ($purpose === 'connect' && ! config('app.cloud')) {
            $license = Tenant::current()?->license;

            $error = blank($license)
                ? __('Add your license key before connecting ClassLink.')
                : $registration->register($license, $request->getSchemeAndHttpHost());

            if ($error) {
                return to_route('settings.tenant.edit')->with('error', $error);
            }
        }

        $nonce = Str::random(64);

        $request->session()->put([
            'classlink.nonce' => $nonce,
            'classlink.purpose' => $purpose,
        ]);

        $query = http_build_query([
            'origin' => $request->getSchemeAndHttpHost(),
            'nonce' => $nonce,
        ]);

        return redirect()->away(rtrim($broker, '/').'/'.config('services.auth_broker.app').'/classlink/start?'.$query);
    }
}
