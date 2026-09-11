<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\FlashesAndRedirects;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateTimezoneController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Handle the incoming request.
     *
     * @return RedirectResponse
     */
    public function __invoke(Request $request)
    {
        $timezones = timezones();

        if (is_string($timezones)) {
            abort(500);
        }

        $data = $request->validate([
            'timezone' => ['required', Rule::in($timezones->keys())],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update($data);

        return $this->flashAndBack();
    }
}
