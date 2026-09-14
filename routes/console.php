<?php

use App\Console\Commands\SendTimeSlotReminders;
use Illuminate\Support\Facades\Schedule;
use Spatie\Activitylog\Commands\CleanActivitylogCommand;

Schedule::command(SendTimeSlotReminders::class)->hourly();
Schedule::command(CleanActivitylogCommand::class)->daily();
