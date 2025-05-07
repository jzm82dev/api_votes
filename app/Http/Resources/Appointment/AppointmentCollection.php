<?php

namespace App\Http\Resources\Appointment;

use Illuminate\Http\Request;
use App\Http\Resources\Appointment\AppointmentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "data" => AppointmentResource::collection($this->collection),
        ];
    }
}
