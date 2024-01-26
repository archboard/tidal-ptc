<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

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
        $alias = $data['model'];
        $response = [];

        if (class_exists($alias)) {
            $alias = (new $alias)->getMorphClass();
        }

        if ($model = Relation::getMorphedModel($alias)) {
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
