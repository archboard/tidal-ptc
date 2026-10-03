<?php

namespace App\Actions;

use App\Models\Activity;
use Spatie\Activitylog\Actions\CleanActivityLogAction as BaseCleanActivityLogAction;

/**
 * The scheduled clean runs without a current tenant and prunes every district's log.
 */
class CleanActivityLogAction extends BaseCleanActivityLogAction
{
    protected function deleteOldActivities(string $cutOffDate, ?string $logName): int
    {
        $query = Activity::withoutTenant()->where('created_at', '<', $cutOffDate);

        if ($logName !== null) {
            $query->inLog($logName);
        }

        return $query->delete();
    }
}
