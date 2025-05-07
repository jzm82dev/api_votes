<?php

namespace App\Http\Resources\Urbanisation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrbanisationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id, 
            'name' => $this->resource->name, 
            'president' => $this->resource->president, 
            'email' => $this->resource->email,
            'hash' => $this->resource->hash,
            'mobile' => $this->resource->mobile,
            'avatar' => $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : 'assets/img/user-06.jpg',
        ];
    }
}
