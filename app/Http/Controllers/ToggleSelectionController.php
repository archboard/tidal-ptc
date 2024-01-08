<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class ToggleSelectionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, School $school, string $model)
    {
        $user = $request->user();

        if ($request->isMethod('delete')) {
            $user->deselectAllModel($model);
        } elseif ($id = $request->input('selectable_id')) {
            $user->toggleSelectedModel($model, $id);
        } else {
            $user->selectAllModel($model, $request->all());
        }

        if ($request->inertia() || ! $request->wantsJson()) {
            session()->flash('success', __('Selection updated successfully.'));

            return back();
        }

        $data = $request->boolean('silent')
            ? []
            : [
                'level' => 'success',
                'message' => __('Selection updated successfully.'),
            ];

        return response()->json($data);
    }
}
