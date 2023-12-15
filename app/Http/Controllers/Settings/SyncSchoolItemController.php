<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyncSchoolItemController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $item)
    {
        $school = $request->school();
        $method = 'syncSchool'.ucfirst($item);
        $provider = $school->tenant->getSisProvider();

        if (method_exists($provider, $method)) {
            $provider->$method($school);
            session()->flash('success', __('Synced successfully'));
        }

        return back();
    }
}
