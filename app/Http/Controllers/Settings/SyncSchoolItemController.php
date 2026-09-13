<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSchoolItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncSchoolItemController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $item): RedirectResponse
    {
        abort_unless(isset(SyncSchoolItem::METHODS[$item]) && $item !== 'school', 404);

        SyncSchoolItem::dispatch($request->school(), $item, $request->user());
        session()->flash('success', __('Sync started. You will be notified when it finishes.'));

        return back();
    }
}
