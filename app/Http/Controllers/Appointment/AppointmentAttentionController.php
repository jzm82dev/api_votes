<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentAttention;

class AppointmentAttentionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $appointment = Appointment::findOrFail($request->appointement_id);
       $appointmentAttention = $appointment->appointment_attention;

       

       $request->request->add(['recipes'=> json_encode($request->recipes)]);

       if( $appointmentAttention){ //update
        $this->authorize('view', $appointmentAttention);
        $appointmentAttention->update($request->all());
        /*$appointmentAttention::update([
            'description' => $request->description,
            'recipes' => json_encode($request->recipes) 
        ]);*/
       }else{ //insert
        $this->authorize('updateAttentionAppoinment', $appointment);
        $appointmentAttention = AppointmentAttention::create($request->all());
            /*$appointmentAttention = AppointmentAttention::create([
                'appointment_id' => $request->appointment_id,
                'patient_id' => $appointment->patient_id,
                'description' => $request->description,
                'recipes' => json_encode($request->recipes) 
            ]);*/
            date_default_timezone_set('Europe/Madrid');
            $now = now();
            $appointment->update([
                'status' => 2,
                'day_attention' => $now //$appointmentAttention->created_at
            ]);
        }
        
        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointmentAttention = $appointment->appointment_attention;

        if( $appointmentAttention){
            $this->authorize('view', $appointmentAttention);
            return response()->json([
                'message' => 200,
                'appo' => $appointment,
                'appointment' => AppointmentResource::make($appointment),
                'appointment_attention' => [
                    'id' => $appointmentAttention->id,
                    'description' => $appointmentAttention->description,
                    'recipes' => $appointmentAttention->recipes ? json_decode($appointmentAttention->recipes) : [],
                    'created_at' => $appointmentAttention->created_at->format("Y-m-d h:i:A") //$appointmentAttention->format("Y-m-d h:i A")
                ]
            ]);
        }else{
            return response()->json([
                'message' => 200,
                'id' => $id,
                'appointment' => AppointmentResource::make($appointment),
                'appointment_attention' => [
                    'id' => NULL,
                    'description' => NULL,
                    'recipes' => [],
                    'created_at' => NULL
                ]
            ]);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
