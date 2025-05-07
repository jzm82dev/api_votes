<?php

namespace App\Http\Resources\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'surname' => $this->resource->surname,
            'email' => $this->resource->email,
            'mobile' => $this->resource->mobile,
            'dni' => $this->resource->dni,
            'birthday' => $this->resource->birthday ? Carbon::parse( $this->resource->birthday)->format("Y/m/d") : '',
            'antecedent_family' => $this->resource->antecedent_family,
            'antecedent_personal' => $this->resource->antecedent_personal,
            'antecedent_allergic' => $this->resource->antecedent_allergic,
            'ta' => $this->resource->ta,
            'temperature' => $this->resource->temperature,
            'fc' => $this->resource->fc,
            'fr' => $this->resource->fr,
            'weight' => $this->resource->weight,
            'current_disease' => $this->resource->current_disease,
            'person' => $this->resource->person ? [
                'id' => $this->resource->person->id,
                'patient_id'=> $this->resource->person->patient_id,
                'name_companion'=> $this->resource->person->name_companion,
                'surname_companion'=> $this->resource->person->surname_companion,
                'mobile_companion'=> $this->resource->person->mobile_companion,
                'relationship_companion'=> $this->resource->person->relationship_companion,
                'name_responsible'=> $this->resource->person->name_responsible,
                'surname_responsible'=> $this->resource->person->surname_responsible,
                'mobile_responsible'=> $this->resource->person->mobile_responsible,
                'relationship_responsible'=> $this->resource->person->relationship_responsible,
            ] :NULL,
            'created_at' => $this->resource->created_at->format("Y-m-d h:i:A")
        ];
    }
}
