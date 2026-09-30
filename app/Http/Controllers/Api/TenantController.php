<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
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
     * Store a newly created resource in storage.
     *
     * @return TenantApiResource
     */
    public function store(StoreTenantRequest $request)
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::create($request->validated());
        $tenant->refresh();
        $tenant->makeCurrent();

        return new TenantApiResource($tenant)->additional($tenant->setupLinks());
    }
}
