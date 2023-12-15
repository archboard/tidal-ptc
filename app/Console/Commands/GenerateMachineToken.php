<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateMachineToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a machine-to-machine api token.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config('app.self_hosted')) {
            $this->error('This command is only available in the cloud version of the app.');

            return static::SUCCESS;
        }

        // Delete any existing tokens
        // so there aren't existing ones floating around somewhere
        DB::table('machine_api_tokens')
            ->whereNotnull('api_token')
            ->delete();

        // Generate a new long token
        $token = Str::random(60);

        DB::table('machine_api_tokens')->insert([
            'api_token' => hash('sha256', $token),
        ]);

        // Spit out the token so we can use it
        $this->info($token);

        return 0;
    }
}
