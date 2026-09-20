<?php

use App\Models\Tenant;
use App\Services\PowerSchoolPluginService;

function pluginXml(string $zipPath): string
{
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $xml = $zip->getFromName('plugin.xml');
    $zip->close();

    return $xml;
}

it('builds a zip with plugin.xml at the root and the query files in place', function () {
    $zipPath = app(PowerSchoolPluginService::class)->build('https://ptc.example.com');

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));

    expect($names->all())->toBe(['plugin.xml', 'queries_root/tidal_ptc.named_queries.xml'])
        ->and($zip->getFromName('plugin.xml'))
        ->toContain('version="1.0.0"')
        ->toContain('base-url="https://ptc.example.com"')
        ->toContain('<openid host="ptc.example.com" port="443">')
        ->not->toContain('{{');

    unlink($zipPath);
});

it('bumps the patch version on the tenant with each download', function () {
    $service = app(PowerSchoolPluginService::class);

    $first = $service->build('https://ptc.example.com', $this->tenant);
    $second = $service->build('http://other.example.com:8080', $this->tenant);

    expect(pluginXml($first))->toContain('version="1.0.0"')
        ->and(pluginXml($second))->toContain('version="1.0.1"')
        ->toContain('<openid host="other.example.com" port="8080">')
        ->and($this->tenant->fresh()->plugin_downloads)->toBe(2);
});

it('stays at 1.0.0 without a saved tenant', function () {
    $service = app(PowerSchoolPluginService::class);

    $service->build('https://ptc.example.com', new Tenant);

    expect(pluginXml($service->build('https://ptc.example.com', new Tenant)))->toContain('version="1.0.0"');
});

it('downloads during installation', function () {
    $this->tenant->update(['sis_config' => null]);

    $this->get(route('install.plugin'))
        ->assertOk()
        ->assertDownload('tidal-ptc-plugin.zip');
});

it("can't be downloaded from tenant settings without permission", function () {
    logIn();

    $this->get(route('settings.tenant.plugin'))->assertForbidden();
});

it('downloads from tenant settings with permission', function () {
    logIn();
    fullPermissions();

    $this->get(route('settings.tenant.plugin'))
        ->assertOk()
        ->assertDownload('tidal-ptc-plugin.zip');
});
