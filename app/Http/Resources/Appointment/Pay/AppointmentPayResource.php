<?php

namespace App\Http\Resources\Appointment\Pay;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentPayResource extends JsonResource
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
            'doctor_id' => $this->resource->doctor_id, 
            'patient_id' => $this->resource->patient_id, 
            'date_appointment' => $this->resource->date_appointment, 
            'date_appointment_format' => Carbon::parse($this->resource->date_appointment)->format("Y-m-d"), 
            'specialitie_id' => $this->resource->specialitie_id, 
            'specialitie' => $this->resource->specialitie ? [
                'id' => $this->resource->specialitie->id,
                'name' => $this->resource->specialitie->name
            ]: NULL,
            'user_id' => $this->resource->user_id, 
            'amount' => $this->resource->amount, 
            'status_pay' => $this->resource->status_pay, 
            'doctor_id' => $this->resource->doctor_id,
            'doctor' => $this->resource->doctor ?[
                'id' => $this->resource->doctor->id,
                'name' => $this->resource->doctor->name,
                'surname' => $this->resource->doctor->surname
            ] : NULL,
            'patient_id' => $this->resource->patient_id,
            'patient' => $this->resource->patient ? [
                'id' => $this->resource->patient->id,
                'dni' => $this->resource->patient->dni,
                'name' => $this->resource->patient->name,
                'surname' => $this->resource->patient->surname,
                'mobile' => $this->resource->patient->mobile,
            ] :  NULL,
            'person_compain' => $this->resource->patient->person ? [
                'id' => $this->resource->patient->person->id,
                'name' => $this->resource->patient->person->name_companion,
                'surname' => $this->resource->patient->person->surname_companion,
            ]: NULL,
            'doctor_schedule_join_hour_id' => $this->resource->doctor_schedule_join_hour_id, 
            'segment_hour' => $this->resource->doctor_schedule_join_hour ? [
                    'id' => $this->resource->doctor_schedule_join_hour->id,
                    'doctor_schedule_day_id' => $this->resource->doctor_schedule_join_hour->doctor_schedule_day_id,
                    'doctor_schedule_hour_id' => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour_id,
                    //'isAvailable' => $isAvailable ? false : true,
                    'format_segment' => [
                        "id" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->id,
                        "hour_start" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                        "hour_end" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                        "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                        "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "hour" => $this->resource->doctor_schedule_join_hour->doctor_schedule_hour->hour,
                    ]
            ]: NULL,
            'user_id' => $this->resource->user_id,
            'user' => $this->resource->user ? [
                'full_name' => $this->resource->user->name.' '.$this->resource->user->surname
            ]: NULL,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A"),
            'payments' => $this->resource->payments->map(function($payment){
                return [
                    'id' => $payment->id,
                    'appointment_id' => $payment->appointment_id,
                    'amount' => $payment->amount,
                    'method_payment' => $payment->method_payment,
                    'created_at' => $payment->created_at->format("Y-m-d h:i:A"),
                ];
            })
        ];
    }
}
