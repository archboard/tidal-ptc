<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassLinkRedirectController extends Controller
{
    /**
     * Sends the browser to the auth broker; the purpose comes from the route default.
     */
    public function __invoke(Request $request, string $purpose): RedirectResponse
    {
        $broker = config('services.auth_broker.url');

        abort_if(empty($broker), 404);

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
