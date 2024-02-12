<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UpdateSelectionVisibilityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model)
    {
        $this->authorize(Permission::update, Str::toModelClass($model));

        $data = $request->validate([
            'can_book' => ['required', 'boolean'],
        ]);

        $request->user()
            ->updateModelSelectionAttributes($model, $data);

        return $this->toSuccess($request, __('Selection updated successfully.'));
    }
}
