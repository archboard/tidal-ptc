<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSchools;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            'sis_config.url' => ['required', 'url'],
            'sis_config.client_id' => ['required', 'uuid'],
            'sis_config.client_secret' => ['required', 'uuid'],
        ]);

        $tenant->fill(Arr::undot($data))
            ->save();
        $tenant->makeCurrent();

        // Kick off job to sync schools
        dispatch(new SyncSchools($tenant));

        session()->flash('success', __('Installation complete. Sync has been started.'));

        return to_route('install.user');
    }

    /**
     * A cloud setup session always has a current tenant; only self-hosted installs create one.
     */
    protected function tenant(Request $request): Tenant
    {
        return config('app.cloud')
            ? Tenant::current()
            : Tenant::fromRequestAndFallback($request);
    }
}
