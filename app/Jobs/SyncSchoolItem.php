<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\User;
use App\Notifications\SyncCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public function handle(): void
    {
        // 'school' means the whole school: run every step in order
        $methods = $this->item === 'school' ? self::METHODS : [self::METHODS[$this->item]];

        foreach ($methods as $method) {
            $this->school->{$method}();
        }

        $this->user->notify(new SyncCompleted(
            $this->item,
            'success',
            __(':item synced successfully.', ['item' => ucfirst($this->item)]),
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $this->user->notify(new SyncCompleted(
            $this->item,
            'error',
            __(':item sync failed. Please try again.', ['item' => ucfirst($this->item)]),
        ));
    }
}
