<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Language;
use App\Enums\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FlashesAndRedirects;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Inertia\ResponseFactory;

class PersonalSettingsController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Show the settings page
     *
     * @return Response|ResponseFactory
     */
    public function edit(Request $request)
    {
        $title = __('Personal settings');
        /** @var User $user */
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
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'timezone' => ['required', 'timezone'],
            'is_24h' => ['required', 'boolean'],
            'locale' => ['required', Rule::enum(Language::class)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update($data);

        return $this->flashAndBack();
    }
}
