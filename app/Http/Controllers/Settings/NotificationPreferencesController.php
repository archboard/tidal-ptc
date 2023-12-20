<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FlashesAndRedirects;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $user->notification_config = $request->collect();
        $user->save();

        return $this->flashAndBack();
    }
}
