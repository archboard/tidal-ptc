<?php

namespace App\Http\Controllers;

use App\Events\PowerSchoolPluginRegistered;
use App\Models\Tenant;
use App\Services\PowerSchoolPluginService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;

/**
 * PowerSchool posts the plugin's OAuth credentials here when the plugin is enabled.
 * Self-hosted installs may not have a tenant yet, so one is started and its install form is filled.
 * PowerSchool expects a 200 every time; failures are reported in callback_result.
 */
class PowerSchoolPluginRegistrationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verify_url' => ['required', 'url'],
            'credentials.client_id' => ['required', 'string'],
            'credentials.client_secret' => ['required', 'string'],
            'callback_data' => ['required', 'string'],
        ]);

        if (! hash_equals(PowerSchoolPluginService::registrationKey($request->host()), $data['callback_data'])) {
            return $this->result(false, 'Invalid registration key');
        }

        $tenant = Tenant::fromRequestAndFallback($request);

        // Cloud tenants are provisioned before their plugin exists
        if (! $tenant->exists && config('app.cloud')) {
            return $this->result(false, 'Unknown district');
        }

        $url = rtrim((string) Uri::of($data['verify_url'])->withPath(''), '/');

        // The time resource is public; the credentials can't be used until PowerSchool finishes enabling the plugin
        try {
            Http::acceptJson()->get($data['verify_url'])->throw();
        } catch (ConnectionException $e) {
            // cURL error 60 is a bad certificate, anything else couldn't reach the host
            return $this->result(false, str_contains($e->getMessage(), 'error 60') ? 'INVALID_CERTIFICATE' : 'HOST_UNKNOWN');
        } catch (HttpClientException $e) {
            return $this->result(false, $e->getMessage());
        }

        $sisConfig = [
            'url' => $url,
            'client_id' => $data['credentials']['client_id'],
            'client_secret' => $data['credentials']['client_secret'],
        ];

        $tenant->name ??= $request->host();
        $tenant->sis_config = $tenant->sis_config->merge($sisConfig);
        $tenant->save();

        if ($tenant->wasRecentlyCreated) {
            PowerSchoolPluginRegistered::dispatch($sisConfig);
        }

        return $this->result(true, 'SUCCESS');
    }

    protected function result(bool $success, string $message): JsonResponse
    {
        return response()->json([
            'callback_result' => $success ? '200' : '500',
            'message' => $message,
            'time' => now('UTC')->format('Y-m-d\TH:i:s.v\Z'),
        ]);
    }
}
