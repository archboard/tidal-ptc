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

    /** @var array<string, string> Sync item => School method */
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
        $this->school->{self::METHODS[$this->item]}();

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
