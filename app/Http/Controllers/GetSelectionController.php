<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetSelectionController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Collection<int, int>
     */
    public function __invoke(Request $request, string $model): Collection
    {
        /** @var User $user */
        $user = $request->user();

        return $user->getModelSelection($model);
    }
}
