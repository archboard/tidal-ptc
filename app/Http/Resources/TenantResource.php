<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Tenant $resource */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'domain' => $this->resource->domain,
            // Districts enter this on the cloud root domain, see DistrictLookupController
            'code' => $this->when(config('app.cloud'), $this->resource->code),
            'license' => $this->resource->license,
            'subscription_started_at' => $this->resource->subscription_started_at,
            'allow_oidc_login' => $this->resource->allow_oidc_login,
            'allow_password_auth' => $this->resource->allow_password_auth,
            'sis' => $this->resource->sis_provider?->label(),
        ];
    }
}
