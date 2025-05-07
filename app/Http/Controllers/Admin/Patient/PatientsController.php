<?php

namespace App\Http\Controllers\Admin\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Termwind\Components\Raw;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Patient\PatientPerson;
use Illuminate\Support\Facades\Redis;
use App\Models\Appointment\Appointment;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\Patient\PatientCollection;
use App\Http\Resources\Appointment\AppointmentCollection;

class PatientsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        // Filtro por nombre de rol
        $search = $request->search;
        $patients = Patient::where(DB::raw("CONCAT(patients.name, ' ',patients.surname, ' ',patients.email)") , 'like', '%'.$search.'%')
                            ->orderBy('id', 'desc')
                            ->paginate(20);

        return response()->json([
            "total" => $patients->total(),
            "patients" => PatientCollection::make($patients)
        ]);
    }


    public function profile(string $id){

        $this->authorize('profile', Patient::class);

        $cachedRecord = Redis::get('patient_profile_#'.$id);
        $patientData = [];

        if(isset($cachedRecord)) {
            $patientData = json_decode($cachedRecord, FALSE);
        }else{
            $patient = Patient::findOrFail($id);
            $totalAppointments = Appointment::where("patient_id", $id)->count();
            $moneyAppointments = Appointment::where("patient_id", $id)->sum("amount");
            $totalPendingAppointments = Appointment::where("patient_id", $id)->where("status",1)->count();
            $pendingAppointments = Appointment::where("patient_id", $id)->where("status",1)->get();
            $appointments = Appointment::where("patient_id", $id)->get();
    
            $patientData = [
                "message" => 200,
                "patient" => PatientResource::make($patient),
                "appointments" => $appointments->map(function($item){
                    return [
                        "id" => $item->id,
                        "patient" => [
                            "id" => $item->patient->id,
                            "fullname" => $item->patient->name.' '.$item->patient->surname
                        ],
                        "doctor" => [
                            "id" => $item->doctor->id,
                            "fullname" => $item->doctor->name.' '.$item->doctor->surname
                        ],
                        'date_appointment' => $item->date_appointment, 
                        'date_appointment_format' => Carbon::parse($item->date_appointment)->format("d M Y"),
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$item->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$item->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "attention_manage" => $item->appointment_attention ? [
                            'id' => $item->appointment_attention->id,
                            'description' => $item->appointment_attention->description,
                            'recipes' => $item->appointment_attention->recipes ? json_decode($item->appointment_attention->recipes) : [],
                            'created_at' => $item->appointment_attention->created_at->format("Y-m-d h:i:A") 
                        ] : NULL,
                        'amount' => $item->amount, 
                        'status_pay' => $item->status_pay, 
                        'status' => $item->status, 
                    ];
                }),
                "pending_appointments" => AppointmentCollection::make($pendingAppointments),
                "total_appointment" => $totalAppointments,
                "total_pending_appointments" => $totalPendingAppointments,
                "total_money" => $moneyAppointments
            ];
    
            Redis::set('patient_profile_#'.$id, json_encode($patientData),'EX', 3600);
        }
        return response()->json($patientData);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Patient::class);

        $validPatient = Patient::where('dni', $request->dni)->first();
        if( $validPatient ){
            return response()->json([
                'message' => 403,
                'message_text' => 'El paciente ya está registrado en nuestros sistemas'
            ]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birthday);
        $request->request->add(["birthday" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $patient = Patient::create($request->all());

        $request->request->add([
            'patient_id' => $patient->id
        ]);
        PatientPerson::create($request->all());

        return response()->json([
            'message' => 200,
            'data' => $request->all(),
            'message_text' => 'Patients alamacenado correctamente'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('view', Patient::class);

        $patient = Patient::findOrFail($id);
        return response()->json( [
            'patient' => PatientResource::make($patient)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('update', Patient::class);

        $patient = Patient::where('dni', $request->emdniil)
                           ->where('id', '<>', $id) 
                           ->first();

        if($patient){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un paciente con este DNI'
            ]);
        }

        $patient = Patient::findOrFail($id);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birthday);
        $request->request->add(["birthday" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);


        $cachedRecord = Redis::get('patient_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('patient_profile_#'.$id);
        }

        $patient->update($request->all());

        $patienPerson = $patient->person;
        if($patienPerson){
            $patienPerson->update($request->all());
        }

        return response()->json([
            'message' => 200,
            'patient' => $patient,
            'message_text' => 'Paciente actualizado correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
    */
    public function deletePatient(string $id)
    {
        
        $this->authorize('delete');

        $patientDelete = Patient::findorFail($id);
        
        $patienPerson = $patientDelete->person;
        
        if($patienPerson){
            $patienPerson->delete();
        }
        
        $cachedRecord = Redis::get('patient_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('patient_profile_#'.$id);
        }
        
        $patientDelete->delete();

        return response()->json([
            'message' => 200
        ]);
    }


    



}
