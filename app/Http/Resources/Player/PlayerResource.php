<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use App\Models\Team\TeamPlayer;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAvailable = TeamPlayer::where("player_id", $this->resource->id)
                                     ->first();
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'surname' => $this->resource->surname,
            'email' => $this->resource->email,
            'mobile' => $this->resource->mobile,
            'address' => $this->resource->address,
            'avatar' => $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : '',
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A"),
            'club' => $this->resource->club ? [
                'id' => $this->resource->club->id,
                'name' => $this->resource->club->name,
            ] : NULL,
            'isAvailable' => $isAvailable ? false : true,
        ];  
    }
}
