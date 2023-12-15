<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateTenantSchoolsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'schools' => ['required', 'array'],
            'schools.*' => [
                'integer',
                Rule::exists('schools', 'id')
                    ->where('tenant_id', $tenant->id),
            ],
        ]);

        if (config('app.self_hosted')) {
            $tenant->schools()
                ->whereIn('id', $data['schools'])
                ->update(['active' => true]);
            $tenant->schools()
                ->whereNotIn('id', $data['schools'])
                ->update(['active' => false]);
        }

        session()->flash('success', __('Schools updated successfully.'));

        return back();
    }
}
