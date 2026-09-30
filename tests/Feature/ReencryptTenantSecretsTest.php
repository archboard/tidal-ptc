<?php

use App\Console\Commands\ReencryptTenantSecrets;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

function useKeys(string $key, array $previousKeys = []): void
{
    config(['app.key' => $key, 'app.previous_keys' => $previousKeys]);
    app()->forgetInstance('encrypter');
    Crypt::clearResolvedInstance('encrypter');
}

it('re-encrypts tenant secrets so they decrypt without the previous key', function () {
    $oldKey = 'base64:'.base64_encode(Encrypter::generateKey('aes-256-cbc'));
    $newKey = 'base64:'.base64_encode(Encrypter::generateKey('aes-256-cbc'));
    useKeys($oldKey);
    DB::table('tenants')->where('id', $this->tenant->id)->update([
        'sis_config' => Crypt::encryptString(json_encode(['client_secret' => 'ps-secret'])),
        'smtp_config' => Crypt::encryptString(json_encode(['password' => 'hunter2'])),
    ]);
    useKeys($newKey, [$oldKey]);

    $this->artisan(ReencryptTenantSecrets::class)->assertSuccessful();

    useKeys($newKey);
    expect($this->tenant->fresh())
        ->sis_config->all()->toBe(['client_secret' => 'ps-secret'])
        ->smtp_config->all()->toBe(['password' => 'hunter2']);
});
