<?php

namespace App\Http\Resources\Court;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $days = $this->resource->day;
        $hoursSelected = collect([]);

        /*foreach ($this->resource->day as $schedule_day) {
            foreach ($schedule_day->court_day_schedule as $schedules_hour) {
                $hoursSelected->push([
                    'id' => $schedules_hour->schedule->id,
                    'day' => $schedule_day->day,
                    'hour' => $schedules_hour->schedule->hour,
                    'hour_start' => $schedules_hour->schedule->hour_start,
                    'hour_end' => $schedules_hour->schedule->hour_end,
                ]);
            }
        }*/

        return [
            'id' => $this->resource->id,  //$this-resource hace referencia al modelo
            'sport_type' => $this->resource->sport_type,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'amount_without_light' => $this->resource->amount_without_light,
            'amount_with_light' => $this->resource->amount_with_light,
            'amount_member_without_light' => $this->resource->amount_member_without_light,
            'amount_member_with_light' => $this->resource->amount_member_with_light,
            'avatar' => $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : '',
            //'days' => $days,
            //'hoursSelected' => $hoursSelected,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
