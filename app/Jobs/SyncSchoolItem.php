<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Notifications\SyncCompleted;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncSchoolItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<string, string> Sync item => School method, in dependency order (sections need staff + courses) */
    public const METHODS = [
        'school' => 'syncFromSis',
        'staff' => 'syncStaff',
        'students' => 'syncStudents',
        'courses' => 'syncCourses',
        'sections' => 'syncSections',
    ];

    public function __construct(public School $school, public string $item, public User $user) {}

    /**
     * Queue the sync and flag it as running so the settings page can show a busy state until it finishes.
     */
    public static function start(School $school, string $item, User $user): void
    {
        // ponytail: TTL caps a stuck flag if the job dies without running failed()
        foreach (self::itemsCoveredBy($item) as $covered) {
            Cache::put(self::syncingKey($school, $covered), true, now()->addHour());
        }
        self::dispatch($school, $item, $user);
    }

    public static function syncingKey(School $school, string $item): string
    {
        return "school.{$school->id}.syncing.{$item}";
    }

    /**
     * 'school' means the whole school: every step in order.
     *
     * @return array<int, string>
     */
    public static function itemsCoveredBy(string $item): array
    {
        return $item === 'school' ? array_keys(self::METHODS) : [$item];
    }

    public function handle(): void
    {
        $items = self::itemsCoveredBy($this->item);

        foreach ($items as $item) {
            $this->school->{self::METHODS[$item]}();
            Cache::forget(self::syncingKey($this->school, $item));
        }

        $text = __(':item synced successfully.', ['item' => ucfirst($this->item)]);

        if (in_array('sections', $items)) {
            self::dispatchEnrollmentBatch($this->school, $this->user);
            $text .= ' '.__('Section enrollment is syncing in the background.');
        }

        $this->user->notify(new SyncCompleted($this->item, 'success', $text));
    }

    /**
     * Enrollment is one SIS call per section, so fan out one job per section as a batch
     * the settings page can poll for progress.
     */
    public static function dispatchEnrollmentBatch(School $school, User $user): void
    {
        $jobs = $school->sections()->get()->map(fn (Section $section) => new SyncSection($section));

        if ($jobs->isEmpty()) {
            return;
        }

        $batch = Bus::batch($jobs)
            ->name("Enrollment sync for {$school->name}")
            ->allowFailures()
            ->finally(fn (Batch $batch) => $user->notify(new SyncCompleted(
                'sections',
                $batch->failedJobs ? 'error' : 'success',
                $batch->failedJobs
                    ? __(':count sections failed to sync enrollment.', ['count' => $batch->failedJobs])
                    : __('Section enrollment synced successfully.'),
            )))
            ->dispatch();

        Cache::put(self::enrollmentBatchKey($school), $batch->id, now()->addDay());
    }

    public static function enrollmentBatchKey(School $school): string
    {
        return "school.{$school->id}.enrollment-batch";
    }

    public function failed(?Throwable $exception): void
    {
        foreach (self::itemsCoveredBy($this->item) as $item) {
            Cache::forget(self::syncingKey($this->school, $item));
        }
        $this->user->notify(new SyncCompleted(
            $this->item,
            'error',
            __(':item sync failed. Please try again.', ['item' => ucfirst($this->item)]),
        ));
    }
}
