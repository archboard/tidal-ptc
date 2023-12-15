<?php

namespace App\Http\Resources;

class TenantApiResource extends TenantResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            ...parent::toArray($request),
            'id' => $this->id,
        ];
    }
}
