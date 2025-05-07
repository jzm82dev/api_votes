<?php

namespace App\Http\Resources\Team;

use App\Models\Team\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $players = $this->resource->players;
        $playersSelected = collect([]);

        foreach ($players as $team_player) {
            
            $playersSelected->push([
                'id' => $team_player->player->id,
                'name' => $team_player->player->name,
                'surname' => $team_player->player->surname,
                'email' => $team_player->player->email,
                'mobile' => $team_player->player->mobile,
                'avatar' => $team_player->player->avatar ? env("APP_URL")."storage/".$team_player->player->avatar : ''
            ]); 
        }

        return [
            'id' => $this->resource->id, 
            'name' => $this->resource->name, 
            'description' => $this->resource->description, 
            'club' => $this->resource->club ? [
                'id' => $this->resource->club->id,
                'name' => $this->resource->club->name,
            ] : NULL,
            'category' => $this->resource->category ? [
                'id' => $this->resource->category->id,
                'name' => $this->resource->category->name,
            ] : NULL,
            'league' => $this->resource->league ? [
                'id' => $this->resource->league->id,
                'name' => $this->resource->league->name,
            ] : NULL,
            'players' => $playersSelected,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
