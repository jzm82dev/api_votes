<?php

namespace App\Http\Resources\User;

use App\Models\Reservation\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /*$reservations = collect();
        if($this->resource->roles->first()->name){
            $AllReservations = Reservation::where('monitor_id', $this->resource->id)->limit(3)->get();
            foreach ($AllReservations as $reservation) {
                $reservations->push([
                    'id' => $reservation->id,
                    'booking_created_at' => $reservation->created_at->format("d/m/Y h:i:A"),
                    'court_id' => $reservation->court_id,
                    'court_name' => $reservation->court->name,
                    'day_week' => $reservation->day_week_number,
                    'booking_date' => $reservation->date,
                    'booking_start' => $reservation->start_time,
                    'booking_end' => $reservation->end_time
                    ]); 
            }
        }*/



        


        return [
            'id' => $this->resource->id,  //$this-resource hace referencia al modelo
            'name' => $this->resource->name,
            'surname' => $this->resource->surname,
            'email' => $this->resource->email,
            'sport_type' => $this->resource->sport_type ? $this->resource->sport_type : 0,
            'mobile' => $this->resource->mobile,
            'gender' => $this->resource->gender,
            'avatar' => $this->resource->avatar ? env("APP_URL")."storage/".$this->resource->avatar : '',
            'reservation_lesson' => $this->resource->reservation_lesson,
            'reservations' => [], //$reservations, 
            'role' => $this->resource->roles->first()->name,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
