<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateTenantSchoolsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Tenant $tenant): RedirectResponse
    {
        $limit = config('app.cloud') ? $tenant->school_limit : null;

        $data = $request->validate([
            'schools' => ['required', 'array', ...($limit !== null ? ["max:{$limit}"] : [])],
            'schools.*' => [
                'integer',
                Rule::exists('schools', 'id')
                    ->where('tenant_id', $tenant->id),
            ],
        ], [
            'schools.max' => __('Your plan covers :max schools. Contact us to add more.'),
        ]);

        $tenant->schools()
            ->whereIn('id', $data['schools'])
            ->update(['active' => true]);
        $tenant->schools()
            ->whereNotIn('id', $data['schools'])
            ->update(['active' => false]);

        session()->flash('success', __('Schools updated successfully.'));

        return back();
    }
}
