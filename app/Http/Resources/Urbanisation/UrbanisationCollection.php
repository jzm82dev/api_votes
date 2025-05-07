<?php

namespace App\Http\Resources\Urbanisation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UrbanisationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "data" => UrbanisationResource::collection($this->collection),
        ];
    }
}
