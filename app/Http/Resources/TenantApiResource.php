<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

class TenantApiResource extends TenantResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>|Arrayable<string, mixed>|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            ...parent::toArray($request),
            'id' => $this->resource->id,
        ];
    }
}
