<?php

namespace App\Actions;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Actions\CleanActivityLogAction as BaseCleanActivityLogAction;

/**
 * The scheduled clean runs without a current tenant and prunes every district's log.
 */
class CleanActivityLogAction extends BaseCleanActivityLogAction
{
    protected function deleteOldActivities(string $cutOffDate, ?string $logName): int
    {
        return Activity::withoutTenant()
            ->where('created_at', '<', $cutOffDate)
            ->when($logName !== null, fn (Builder $query) => $query->inLog($logName))
            ->delete();
    }
}
