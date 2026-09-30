<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartCloudSetupController extends Controller
{
    /**
     * Opens the install wizard to this session, see SelfHostedOrSetup.
     */
    public function __invoke(Request $request, int $tenant): RedirectResponse
    {
        $request->session()->put('setup_tenant_id', $tenant);

        return $request->tenant()->installed()
            ? to_route('install.user')
            : redirect('/install');
    }
}
