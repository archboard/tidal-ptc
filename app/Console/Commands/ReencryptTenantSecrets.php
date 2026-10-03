<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Step two of an APP_KEY rotation: rewrites each tenant's encrypted columns with the
 * current key so the old one can be dropped from APP_PREVIOUS_KEYS. Works on raw rows
 * because an encrypted cast doesn't see an unchanged value as dirty.
 */
class ReencryptTenantSecrets extends Command
{
    /** @var list<string> */
    protected const array COLUMNS = ['sis_config', 'smtp_config'];

    protected $signature = 'key:reencrypt';

    protected $description = 'Re-encrypt tenant secrets with the current APP_KEY after a key rotation';

    public function handle(): int
    {
        $count = 0;

        DB::table('tenants')
            ->select(['id', ...self::COLUMNS])
            ->lazyById()
            ->each(function (object $tenant) use (&$count) {
                $values = collect(self::COLUMNS)
                    ->filter(fn (string $column) => $tenant->{$column} !== null)
                    ->mapWithKeys(fn (string $column) => [
                        $column => Crypt::encryptString(Crypt::decryptString($tenant->{$column})),
                    ]);

                if ($values->isNotEmpty()) {
                    DB::table('tenants')->where('id', $tenant->id)->update($values->all());
                    $count++;
                }
            });

        $this->info("Re-encrypted secrets for {$count} tenant(s).");

        return self::SUCCESS;
    }
}
