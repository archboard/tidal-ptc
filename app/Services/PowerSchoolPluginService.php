<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Builds the PowerSchool plugin zip for a given installation URL.
 * Each download is recorded so the plugin version increases, which
 * PowerSchool requires before it will accept an updated plugin.
 */
class PowerSchoolPluginService
{
    public function __construct(
        protected string $sourcePath = '',
    ) {
        $this->sourcePath = $sourcePath ?: storage_path('tidal-ptc-plugin');
    }

    /**
     * Builds the zip and returns its path. The caller owns the temp file.
     */
    public function build(string $url, ?Tenant $tenant = null): string
    {
        $parts = parse_url($url);
        $host = $parts['host'];
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        $pluginXml = Blade::render(File::get("{$this->sourcePath}/plugin.xml.stub"), [
            'version' => $this->nextVersion($tenant),
            'baseUrl' => rtrim($url, '/'),
            'host' => $host,
            'port' => $port,
        ]);

        $zipPath = tempnam(sys_get_temp_dir(), 'tidal-ptc-plugin');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('plugin.xml', $pluginXml);

        foreach (File::allFiles($this->sourcePath) as $file) {
            if ($file->getExtension() === 'xml') {
                $zip->addFile($file->getPathname(), $file->getRelativePathname());
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Records the download on the tenant and returns the version for it:
     * the patch number is how many times the plugin was downloaded before,
     * so the first download (and any download before a tenant exists) is 1.0.0.
     */
    protected function nextVersion(?Tenant $tenant): string
    {
        if (! $tenant?->exists) {
            return '1.0.0';
        }

        $tenant->increment('plugin_downloads');

        return '1.0.'.($tenant->refresh()->plugin_downloads - 1);
    }
}
