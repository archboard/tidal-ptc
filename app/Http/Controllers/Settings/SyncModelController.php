<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSchoolItem;
use App\Models\Contracts\ExistsInSis;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncModelController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $model, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($className = Relation::getMorphedModel($model)) {
            $instance = $className::find($id);

            if ($instance instanceof School) {
                // Whole-school syncs are slow; queue them and notify on completion
                SyncSchoolItem::dispatch($instance, 'school', $user);
                session()->flash('success', __('Sync started. You will be notified when it finishes.'));
            } elseif ($instance instanceof ExistsInSis) {
                $instance->syncFromSis();
                session()->flash('success', __('Sync successful.'));
            }
        }

        return back();
    }
}
