<?php

namespace App\Jobs;

use App\Models\Section;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Enrollment is one SIS call per section, so a whole school is fanned out one job per section.
 */
class SyncSection implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public Section $section) {}

    public function handle(): void
    {
        $this->section->syncFromSis();
    }
}
