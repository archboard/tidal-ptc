<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Services\ModelClassService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ToggleHiddenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'model' => ['required'],
            'id' => ['required', 'integer'],
        ]);
        $response = [];

        if ($model = Str::toModelClass($data['model'])) {
            $this->authorize(Permission::update, $model);

            if ($instance = $model::find($data['id'])) {
                $instance->toggleHiddenFlag()->save();
                $response = [
                    'level' => 'success',
                    'message' => __('Updated status successfully.'),
                ];
                session()->flash('success', __('Updated status successfully.'));
            }
        }

        if ($request->inertia() || ! $request->wantsJson()) {
            return back();
        }

        return response()->json($response);
    }
}
