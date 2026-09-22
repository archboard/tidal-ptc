<?php

namespace App\Http\Controllers;

use App\Enums\Language;
use App\Http\Controllers\Traits\FlashesAndRedirects;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateLocaleController extends Controller
{
    use FlashesAndRedirects;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::enum(Language::class)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update($data);

        return $this->flashAndBack();
    }
}
