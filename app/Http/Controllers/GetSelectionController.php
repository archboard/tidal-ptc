<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetSelectionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->getModelSelection($model);
    }
}
