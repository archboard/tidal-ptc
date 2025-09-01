<?php

namespace App\Http\Controllers;

use App\Forms\Traits\ValidatesTenantFields;
use App\Models\Tenant;
use Illuminate\Http\Request;

class UpdateSmtpSettingsController extends Controller
{
    use ValidatesTenantFields;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Tenant $tenant)
    {
        $data = $request->validate($this->smtpRules());

        $tenant->smtp_config = collect($data);
        $tenant->save();

        session()->flash('success', __('SMTP settings updated.'));

        return to_route('settings.tenant.edit');
    }
}
