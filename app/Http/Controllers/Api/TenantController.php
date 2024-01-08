<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantApiResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'license' => ['required', 'uuid', 'unique:tenants'],
            'domain' => ['required', 'string', 'unique:tenants'],
            'custom_domain' => ['nullable', 'string', 'unique:tenants'],
            'subscription_started_at' => ['required', 'date'],
            'subscription_expires_at' => ['required', 'date'],
        ]);

        /** @var Tenant $tenant */
        $tenant = Tenant::create(Arr::except($data, 'email'));
        $tenant->makeCurrent();

        return new TenantApiResource($tenant);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
