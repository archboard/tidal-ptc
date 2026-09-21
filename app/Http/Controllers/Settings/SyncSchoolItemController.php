<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSchoolItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncSchoolItemController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $item): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(isset(SyncSchoolItem::METHODS[$item]) && $item !== 'school', 404);

        SyncSchoolItem::start($request->school(), $item, $user);
        session()->flash('success', __('Sync started. You will be notified when it finishes.'));

        return back();
    }
}
