<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FlashesAndRedirects;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->notification_config = $request->collect();
        $user->save();

        return $this->flashAndBack();
    }
}
