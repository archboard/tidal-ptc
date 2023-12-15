<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Testing Routes
|--------------------------------------------------------------------------
|
| These routes are for Cypress tests to accommodate the needs of e2e testing
|
*/

Route::prefix('_testing')->group(function () {
    $email = 'user@example.com';
    $tenantAttributes = [
        'name' => 'Organization',
        'ps_url' => env('POWERSCHOOL_ADDRESS'),
        'ps_client_id' => env('POWERSCHOOL_CLIENT_ID'),
        'ps_secret' => env('POWERSCHOOL_CLIENT_SECRET'),
        'license' => \Ramsey\Uuid\Uuid::uuid4(),
        'allow_password_auth' => false,
        'subscription_started_at' => now(),
    ];

    /**
     * Creates a user session for the given tenant
     */
    Route::get('/session/new', function (Request $request) use ($email, $tenantAttributes) {
        $tenant = \App\Models\Tenant::updateOrCreate(['domain' => $request->getHost()], $tenantAttributes);

        \App\Models\User::where('email', $email)->delete();

        /** @var \App\Models\User $user */
        $user = $tenant->users()->save(\App\Models\User::factory()->make(['email' => $email]));

        auth()->login($user);

        return response()->json();
    });

    /**
     * Logs the user out and deletes any user with that email
     */
    Route::get('/session/logout', function (Request $request) use ($email, $tenantAttributes) {
        $user = $request->user();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            $user->delete();
        }

        \App\Models\User::where('email', $email)->delete();
        \App\Models\Tenant::current()->update($tenantAttributes);

        return response()->json();
    });

    Route::post('/permissions', function (Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $user->allow($request->input('permissions'));

        return response()->json();
    });
});
