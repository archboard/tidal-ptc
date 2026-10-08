<?php

use App\Console\Commands\SendTimeSlotReminders;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Support\Facades\Schedule;
use Spatie\Activitylog\Commands\CleanActivitylogCommand;

Schedule::command(SendTimeSlotReminders::class)->hourly();
Schedule::command(CleanActivitylogCommand::class)->daily();
Schedule::command(FreshCommand::class, ['--force', '--seed', '--seeder' => DemoSeeder::class])
    ->hourly()
    ->when(fn () => config('app.demo'));
