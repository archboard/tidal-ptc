<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Sis;
use App\Forms\SmtpForm;
use App\Forms\TenantSettingsForm;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    /**
     * Shows the tenant settings form
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Tenant $tenant)
    {
        $title = __('Tenant Settings');
        $tenantForm = new TenantSettingsForm($tenant);
        $smtpForm = config('app.self_hosted')
            ? new SmtpForm($tenant)
            : null;
        $schools = $tenant->schools()
            ->orderBy('name')
            ->get();

        return inertia('settings/Tenant', [
            'title' => $title,
            'tenant' => Tenant::current()->toArray(),
            'smtpForm' => $smtpForm?->toInertia(),
            'tenantForm' => $tenantForm->toInertia(),
            'sisOptions' => Sis::selectOptions(),
            'schools' => $schools->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'active' => $school->active,
            ]),
            'editable' => config('app.self_hosted'),
        ])->withViewData(compact('title'));
    }

    /**
     * Updates attributes for the tenant
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate(
            (new TenantSettingsForm($tenant))->rules()
        );

        $tenant->update($data);

        session()->flash('success', __('Settings updated successfully.'));

        return to_route('settings.tenant.edit');
    }
}
