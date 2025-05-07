<?php

namespace App\Http\Resources\Couple;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoupleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $players = $this->resource->players;
        $substitutePlayers = $this->resource->substitutePlayer;
        $playersSelected = collect([]);
        $substitutePlayersSelected = collect([]);

        foreach ($players as $couple_player) {
            
            $playersSelected->push([
                'id' => $couple_player->user->id,
                'name' => $couple_player->user->club_user != NULL ? $couple_player->user->club_user->name : $couple_player->user->name,
                'surname' => $couple_player->user->club_user != NULL ? $couple_player->user->club_user->surname : $couple_player->user->name,
                'email' => $couple_player->user->email,
                'mobile' => $couple_player->user->mobile,
                'substitute' => $couple_player->substitute,
                //'avatar' => $couple_player->user->avatar ? env("APP_URL")."storage/".$couple_player->player->avatar : ''
            ]); 
        }
        foreach ($substitutePlayers as $couple_player) {
            
            $substitutePlayersSelected->push([
                'id' => $couple_player->user->id,
                'name' => $couple_player->user->name,
                'surname' => $couple_player->user->surname,
                'email' => $couple_player->user->email,
                'mobile' => $couple_player->user->mobile,
                'substitute' => $couple_player->substitute,
                //'avatar' => $couple_player->user->avatar ? env("APP_URL")."storage/".$couple_player->player->avatar : ''
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
            'substitute_player' => $substitutePlayersSelected,
            'schedule_not_play' => $this->resource->scheduleNotPlay,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
