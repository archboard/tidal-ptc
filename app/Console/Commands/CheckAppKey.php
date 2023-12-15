<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CheckAppKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if there is an app key and generates one if it does not exist';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! config('app.key')) {
            Artisan::call('key:generate --force');
        }

        return 0;
    }
}
