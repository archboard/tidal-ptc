<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class SyncBrokerOrigins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    /**
     * Takes plain values rather than the tenant, which may already be deleted when this runs.
     *
     * @param  array<int, string>  $origins  Origins to register under the license.
     * @param  array<int, string>  $removedOrigins  Origins to unregister.
     */
    public function __construct(
        protected string $license,
        protected array $origins,
        protected array $removedOrigins,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $broker = config('services.auth_broker');
        $base = rtrim($broker['url'], '/')."/api/apps/{$broker['app']}";
        $http = Http::withToken($broker['machine_token'])->acceptJson()->timeout(10);

        if ($this->origins !== []) {
            $http->put("$base/licenses/{$this->license}", ['kind' => 'cloud'])->throw();
        }

        foreach ($this->origins as $origin) {
            $http->put("$base/origins", ['license' => $this->license, 'origin' => $origin])->throw();
        }

        foreach ($this->removedOrigins as $origin) {
            $http->delete("$base/origins", ['origin' => $origin])->throw();
        }
    }
}
