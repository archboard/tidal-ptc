<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSchools;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Silber\Bouncer\BouncerFacade;

class InstallationController extends Controller
{
    public function index(Request $request)
    {
        $title = __('Installation');
        $tenant = Tenant::fromRequestAndFallback($request);
        $fields = $tenant->getInstallationFields();

        return inertia('Install', [
            'title' => $title,
            'form' => $fields->toInertia(),
            'fields' => $fields->toResource(),
            'email' => $request->user()?->email,
        ])->withViewData(compact('title'));
    }

    public function store(Request $request)
    {
        $tenant = Tenant::fromRequestAndFallback($request);

        $data = $request->validate(
            $tenant->getInstallationFields()
                ->toValidationRules()
        );

        $tenant->fill(Arr::undot(Arr::except($data, 'email')))
            ->save();
        $tenant->makeCurrent();

        // Save the user and give them full privileges
        /** @var User $user */
        $user = $tenant->users()->updateOrCreate(Arr::only($data, 'email'));
        BouncerFacade::allow($user)->everything();
        auth()->login($user);

        // Kick off job to sync schools
        dispatch(new SyncSchools($tenant));

        session()->flash('success', __('Installation complete. Sync has been started.'));

        return to_route('settings.tenant.edit');
    }
}
