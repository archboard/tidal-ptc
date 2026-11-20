<?php

namespace App\Http\Controllers;

use App\Events\PowerSchoolPluginRegistered;
use App\Jobs\SyncSchools;
use App\Models\Tenant;
use App\Rules\ValidLicense;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Response;

class InstallationController extends Controller
{
    public function index(Request $request): Response
    {
        $title = __('Installation');
        $tenant = $this->tenant($request);

        return inertia('Install', [
            'title' => $title,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'sisConfig' => $tenant->sis_config->toArray(),
            'isCloud' => config('app.cloud'),
            'channel' => PowerSchoolPluginRegistered::channelName($request->host()),
        ])->withViewData(compact('title'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Cloud domains are set at provisioning
            ...(config('app.cloud') ? [] : [
                'domain' => ['required', Rule::unique('tenants', 'domain')->ignoreModel($tenant)],
            ]),
            'enable_classlink' => ['boolean'],
            'license' => [
                'exclude_unless:enable_classlink,true,1',
                'required',
                'uuid',
                new ValidLicense,
                Rule::unique('tenants', 'license')->ignoreModel($tenant),
            ],
            'sis_config.url' => ['required', 'url'],
            'sis_config.client_id' => ['required', 'uuid'],
            'sis_config.client_secret' => ['required', 'uuid'],
        ]);

        $enableClassLink = ! config('app.cloud') && ($data['enable_classlink'] ?? false);

        $tenant->fill(Arr::undot(Arr::except($data, 'enable_classlink')))
            ->save();
        $tenant->makeCurrent();

        // Kick off job to sync schools
        dispatch(new SyncSchools($tenant));

        $success = __('Installation complete. Sync has been started.');

        if ($enableClassLink) {
            $error = $this->registerWithBroker($request, $tenant->license);

            $error ? session()->flash('error', $error) : $success .= ' '.__('ClassLink sign-in is enabled.');
        }

        session()->flash('success', $success);

        return to_route('install.user');
    }

    /**
     * Proves control of this origin to the auth broker; a failure never blocks the install.
     *
     * @return string|null The user-facing error, or null when registered.
     */
    protected function registerWithBroker(Request $request, string $license): ?string
    {
        $token = Str::random(48);
        Cache::put('auth_broker.challenge', $token, now()->addMinutes(10));

        try {
            $response = Http::acceptJson()->timeout(15)->post(
                config('services.auth_broker.url').'/api/apps/'.config('services.auth_broker.app').'/registrations',
                ['license' => $license, 'origin' => $request->getSchemeAndHttpHost(), 'token' => $token],
            );
        } catch (ConnectionException) {
            return __('ClassLink registration failed: :reason', ['reason' => __('could not reach the auth broker')]);
        } finally {
            Cache::forget('auth_broker.challenge');
        }

        return $response->successful()
            ? null
            : __('ClassLink registration failed: :reason', ['reason' => Str::limit(trim($response->body()), 200)]);
    }

    /**
     * A cloud setup session always has a current tenant; only self-hosted installs create one.
     */
    protected function tenant(Request $request): Tenant
    {
        return config('app.cloud')
            ? Tenant::current() ?? abort(404)
            : Tenant::fromRequestAndFallback($request);
    }
}
