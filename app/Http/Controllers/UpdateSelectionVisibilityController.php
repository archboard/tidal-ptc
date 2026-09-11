<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UpdateSelectionVisibilityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model): JsonResponse|RedirectResponse
    {
        $this->authorize(Permission::update, Str::toModelClass($model));

        $data = $request->validate([
            'can_book' => ['required', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->updateModelSelectionAttributes($model, $data);

        return $this->toSuccess($request, __('Selection updated successfully.'));
    }
}
