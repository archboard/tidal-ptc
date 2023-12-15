<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Contracts\ExistsInSis;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class SyncModelController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model, int $id)
    {
        if ($className = Relation::getMorphedModel($model)) {
            $instance = $className::find($id);

            if ($instance instanceof ExistsInSis) {
                $instance->syncFromSis();
                session()->flash('success', __('Sync successful.'));
            }
        }

        return back();
    }
}
