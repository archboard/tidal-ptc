<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertTenantRequest;
use App\Http\Resources\TenantApiResource;
use App\Models\Tenant;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index()
    {
        $tenants = Tenant::query()
            ->orderBy('created_at')
            ->paginate();

        return TenantApiResource::collection($tenants);
    }

    /**
     * Creates or updates the tenant for a billing license: provisioning, retries and
     * renewals are all this call. The resource responds 201 when it created the tenant.
     * Setup links come back until the district has an admin, so calling it again reissues them.
     */
    public function update(UpsertTenantRequest $request, string $license): TenantApiResource
    {
        $tenant = $request->existingTenant() ?? new Tenant(['license' => $license]);
        $tenant->fill($request->validated())->save();

        $links = $tenant->execute(fn (Tenant $tenant) => $tenant->hasDistrictAdmin()
            ? ['setup_url' => null, 'plugin_url' => null]
            : $tenant->setupLinks());

        return new TenantApiResource($tenant->refresh())->additional($links);
    }
}
