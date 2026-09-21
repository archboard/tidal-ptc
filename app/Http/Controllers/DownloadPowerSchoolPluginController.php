<?php

namespace App\Http\Controllers;

use App\Services\PowerSchoolPluginService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadPowerSchoolPluginController extends Controller
{
    public function __invoke(Request $request, PowerSchoolPluginService $plugin): BinaryFileResponse
    {
        return response()
            ->download($plugin->build($request->getSchemeAndHttpHost(), $request->tenant()), 'tidal-ptc-plugin.zip')
            ->deleteFileAfterSend();
    }
}
