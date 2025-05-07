<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $scheduleWeeklyHours = collect([]);

        foreach ($this->resource->clubScheduleDay as $schedule_day) {
            $scheduleWeeklyHours->push([
                'id' => $schedule_day->id,
                'day_id' => $schedule_day->day_id,
                'day_name' => $schedule_day->day_name,
                'closed' => $schedule_day->closed,
                'hours' => $schedule_day->schedulesHours->map(function($hour_item){
                    return [
                        'id' => $hour_item->id,
                        'opening_time' => $hour_item->opening_time,
                        'opening_time_id' => $hour_item->opening_time_id,
                        'closing_time' => $hour_item->closing_time,
                        'closing_time_id' => $hour_item->closing_time_id
                    ];
                })
            ]);
        }
        

        $scheduleSpecialDays = collect([]);

        foreach ($this->resource->clubScheduleSpecialDay as $special_day) {
            $scheduleSpecialDays->push([
                'id' => $special_day->id,
                'date' => $special_day->date,
                'information' => $special_day->information,
                'closed' => $special_day->closed,
                'day_name' => $special_day->closed,
                'hours' => $special_day->schedulesSpecialDayHours->map(function($hour_item){
                    return [
                        'id' => $hour_item->id,
                        'opening_time' => $hour_item->opening_time,
                        'opening_time_id' => $hour_item->opening_time_id,
                        'closing_time' => $hour_item->closing_time,
                        'closing_time_id' => $hour_item->closing_time_id
                    ];
                })
            ]);
        }


        return [
            'id' => $this->resource->id, 
            'cif' => $this->resource->cif,
            'name' => $this->resource->name, 
            'manager' => $this->resource->club_manager, 
            'email' => $this->resource->email,
            'hash' => $this->resource->hash,
            'mobile' => $this->resource->mobile,
            'users_can_book' => $this->resource->users_can_book,
            'additional_info' => $this->resource->additional_information,
            'courts' => $this->courts,
            'avatar' => $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : 'assets/img/user-06.jpg',
            'schedule_week_hours' => $scheduleWeeklyHours,
            'total_users' => count($this->users), 
            'rrhh' => $this->resource->social_links,
            'special_days_schedule' => $scheduleSpecialDays,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
