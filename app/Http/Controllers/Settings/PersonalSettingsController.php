<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FlashesAndRedirects;
use Illuminate\Http\Request;

class PersonalSettingsController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Show the settings page
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Request $request)
    {
        $title = __('Personal settings');
        $user = $request->user();

        return inertia('settings/Personal', [
            'title' => $title,
            'hasPassword' => (bool) $user->password,
            'userNotifications' => $user->notification_config ?? collect(),
            'notificationOptions' => $user->getNotificationOptions()
                ->map(fn (NotificationEvent $event) => [
                    'label' => $event->label(),
                    'key' => $event->value,
                    'description' => $event->description(),
                ]),
        ])->withViewData(compact('title'));
    }

    /**
     * Updates a users name, email, and password
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'timezone' => ['required', 'timezone'],
            'is_24h' => ['required', 'boolean'],
        ]);

        $request->user()
            ->update($data);

        return $this->flashAndBack();
    }
}
