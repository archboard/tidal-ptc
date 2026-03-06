<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSchools;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class InstallationController extends Controller
{
    public function index(Request $request)
    {
        $title = __('Installation');
        $tenant = Tenant::fromRequestAndFallback($request);

        return inertia('Install', [
            'title' => $title,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'sisConfig' => $tenant->sis_config->toArray(),
        ])->withViewData(compact('title'));
    }

    public function store(Request $request)
    {
        $tenant = Tenant::fromRequestAndFallback($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', Rule::unique('tenants', 'domain')->ignoreModel($tenant)],
            ...(config('app.cloud') ? [
                'custom_domain' => [
                    'nullable',
                    Rule::unique('tenants', 'domain')->ignoreModel($tenant),
                    Rule::unique('tenants', 'custom_domain')->ignoreModel($tenant),
                ],
            ] : []),
            'sis_config.url' => ['required', 'url'],
            'sis_config.client_id' => ['required', 'uuid'],
            'sis_config.client_secret' => ['required', 'uuid'],
        ]);

        $tenant->fill(Arr::undot(Arr::except($data, 'email')))
            ->save();
        $tenant->makeCurrent();

        // Kick off job to sync schools
        dispatch(new SyncSchools($tenant));

        session()->flash('success', __('Installation complete. Sync has been started.'));

        return to_route('install.user');
    }
}
