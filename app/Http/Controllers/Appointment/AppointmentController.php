<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Models\Doctor\Specialities;
use App\Http\Controllers\Controller;
use App\Models\Patient\PatientPerson;
use App\Models\Appointment\Appointment;
use App\Models\Doctor\DoctorScheduleDay;
use App\Models\Doctor\DoctorScheduleHour;
use App\Models\Appointment\AppointmentPay;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Symfony\Component\CssSelector\Node\Specificity;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Appointment\AppointmentCollection;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
       
        $this->authorize('viewAny', Appointment::class);

        $specialitieId = $request->specialitie_id;
        $name_doctor = $request->search;
        $date = $request->date;
        $doctor = auth('api')->user();

        $appointments = Appointment::filterAvanced($specialitieId, $name_doctor, $date, $doctor)->orderBy("id", "desc")->paginate(20);
        /*
            $appointments = Appointment::where("specialitie_id", $specialitieId)
                                   ->whereHas("doctor", function($q) use($name_doctor){
                                        $q->where("name", "like", "%".$name_doctor."%")
                                        ->orWhere("surname", "like", "%".$name_doctor."%") ;
                                        })
                                    ->whereDate('date_appointment', Carbon::parse($date)->format("Y-m-d"));
        */
        return response()->json([
            'message' => 200,
            'total' => $appointments->total(),
            'appointments' => AppointmentCollection::make($appointments)
        ]);
    }

    public function filter(Request $request){

        
        $dateAppointment = $request->date_appointment;
        //$date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->dateAppointment);
        //$dateAppointment = Carbon::parse($date_clean)->format("Y-m-d");

        $hourAppointment = $request->hour_appointment;
        $specialitieId = $request->specialitie;

        //date_default_timezone_set('Europe/Madrid');
        //Carbon::setLocale('es');
        //DB::statement("SET lc_time_names = 'es_ES'");
        
        $dayName = Carbon::parse($dateAppointment)->dayName;
        //dd($dayName);

        $result = DoctorScheduleDay::where("day", "like", '%'.$dayName.'%')
                                         ->whereHas("doctor", function($q) use($specialitieId){
                                            $q->where("specialitie_id", $specialitieId);
                                         })->whereHas("schedules_hours", function($q) use($hourAppointment){
                                            $q->whereHas("doctor_schedule_hour", function($qsub) use($hourAppointment){
                                                $qsub->where("hour", $hourAppointment);
                                            });
                                         })->get();
        $doctors = collect([]);
        
        foreach ($result as $item) {
            //$segments = collect([]);
            //foreach ($item->schedules_hours as $hours) {
            //    if( $hours->doctor_schedule_hour->hour == $hourAppointment){
            //        $segments->push([
            //            'id' => $hours->doctor_schedule_hour->id,
            //            'hour_start' => $hours->doctor_schedule_hour->hour_start,
            //            'hour_end' => $hours->doctor_schedule_hour->hour_end,
            //        ]);
            //    }
            //}
            

            $segments2 = DoctorScheduleJoinHour::where("doctor_schedule_day_id", $item->id)
                                               ->whereHas("doctor_schedule_hour", function($q) use($hourAppointment){
                                                    $q->where("hour", $hourAppointment);
                                               })->get();
            $doctors->push([
                'doctor' => [
                    'id' => $item->doctor->id,
                    'full_name' => $item->doctor->name.' '.$item->doctor->surname,
                    'specialitie' => [
                        'id' => $item->doctor->specialitie->id,
                        'name' => $item->doctor->specialitie->name
                    ]
                ],
                'test' => $dateAppointment,
                'segments' => $segments2->map(function($segment) use($dateAppointment){
                    $isAvailable = Appointment::where('doctor_schedule_join_hour_id', $segment->id )
                                              ->whereDate("date_appointment", Carbon::parse($dateAppointment)->format("Y-m-d"))
                                              ->first();
                    return [
                        'id' => $segment->id,
                        'doctor_schedule_day_id' => $segment->doctor_schedule_day_id,
                        'doctor_schedule_hour_id' => $segment->doctor_schedule_hour_id,
                        'isAvailable' => $isAvailable ? false : true,
                        'format_segment' => [
                            "id" => $segment->doctor_schedule_hour->id,
                            "hour_start" => $segment->doctor_schedule_hour->hour_start,
                            "hour_end" => $segment->doctor_schedule_hour->hour_end,
                            "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_start)->format("h:i A"),
                            "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$segment->doctor_schedule_hour->hour_end)->format("h:i A"),
                            "hour" => $segment->doctor_schedule_hour->hour,
                        ]
                    ];
                })
            ]);
        }
        
        return response()->json([
            "message" => 200,
            "doctors" => $doctors,
            "dateAppointment" => $dayName
        ]);
    }

    public function config(){

        $this->authorize('filter', Appointment::class);

        $specialities = Specialities::where('state', 1)->get();
        $hours = [
            [
                "id" => '08',
                "name" => "8:00 AM"
            ],
            [
                "id" => '09',
                "name" => "9:00 AM"
            ],
            [
                "id" => '10',
                "name" => "10:00 AM"
            ],
            [
                "id" => '11',
                "name" => "11:00 AM"
            ],
            [
                "id" => '12',
                "name" => "12:00 PM"
            ],
            [
                "id" => '13',
                "name" => "1:00 PM"
            ],
            [
                "id" => '14',
                "name" => "2:00 PM"
            ],
            [
                "id" => '15',
                "name" => "3:00 PM"
            ],
            [
                "id" => '16',
                "name" => "4:00 PM"
            ],
            [
                "id" => '17',
                "name" => "5:00 PM"
            ]
        ];

        return response()->json([
            "message" => 200,
            "hours" => $hours,
            "specialities" => $specialities
        ]);
    }

    public function calendar( Request $request){

        $specialitieId = $request->specialitie_id;
        $doctorName = $request->search_doctor;
        $patientName = $request->search_patient;
        $doctor = auth('api')->user();

        $appointments = Appointment::filterAvancedPay($specialitieId, $doctorName, $patientName, null, null, $doctor )
                                   ->orderBy("id", "desc")->get();
        
        return response()->json([
            "appointment" => $appointments->map(function($appointment) {
                return [
                    "id" => $appointment->id,
                    "title" => 'Appointment - '.$appointment->doctor->name.' '.$appointment->doctor->surname.' - '.$appointment->specialitie->name,
                    "start" => Carbon::parse($appointment->date_appointment)->format("Y-m-d").'T'.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start,
                    "end" => Carbon::parse($appointment->date_appointment)->format("Y-m-d").'T'.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end,
                ];
            })
        ]);


    }

    public function findPatient( Request $request){

        $this->authorize('view', Appointment::class);

        $dni = $request->get('dni');
        $patient = Patient::where('dni', $dni)->first();

        if(!$patient){
            return response()->json([
                'message' => 403
            ]);
        }else{
            return response()->json([
                'message' => 200,
                'name' => $patient->name,
                'surname' => $patient->surname,
                'mobile' => $patient->mobile,
                'dni' => $patient->dni
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);
        
        $patient = null;
        $patient = Patient::where('dni', $request->dni)->first();
        if(!$patient){ // no exist patient so we create
            $patient = Patient::create([
                    'name' => $request->name,
                    'surname' => $request->surname,
                    'mobile' => $request->mobile,
                    'dni' => $request->dni
                ]);
            if( $request->name_companion != '' || $request->surname_companion != ''){
                PatientPerson::create([
                    'patient_id' => $patient->id,
                    'name_companion' => $request->name_companion,
                    'surname_companion' => $request->surname_companion 
                ]);
            }
        }else{
            $patient->person->update([
                'name_companion' => $request->name_companion,
                'surname_companion' => $request->surname_companion 
            ]);
        }

        $status = 2;
        if( $request->total_amount == $request->amount_add){
            $status = 1;
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_appointment);
        $request->request->add(["date_appointment" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $appointment = Appointment::create([
                'doctor_id' => $request->doctor_id,
                'patient_id' => $patient->id,
                'date_appointment' => $request->date_appointment,
                'specialitie_id' => $request->specialitie_id,
                'doctor_schedule_join_hour_id' => $request->doctor_schedule_join_hour_id,
                'user_id' => auth("api")->user()->id,
                'amount' => $request->total_amount,
                'status_pay' => $status
            ]);

        AppointmentPay::create([
            'appointment_id' => $appointment->id,
            'amount' => $request->amount_add,
            'method_payment' => $request->payment_method
        ]);   


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
       // dd($appointment);
        $this->authorize('view', $appointment);

        $total = $appointment->payments->sum('amount');
    
        return response()->json([
            'appointment' => AppointmentResource::make($appointment),
            'total_paid' => $total,
            'payments' => $appointment->payments
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appointmentUpdate = Appointment::findOrFail($id);
     
        $this->authorize('update', $appointmentUpdate);
        $totalAmountPays = $appointmentUpdate->payments;
        $totalPaid = 0;
        foreach ($totalAmountPays as $pay) {
            $totalPaid += $pay->amount;
        }

        if( $request->total_amount < $appointmentUpdate->payments->sum('amount') ){
            return response()->json([
                'message' => 403,
                'message_text' => "El costo total es inferor a lo ya pagado por el paciente"
            ]);
        }

        $status = 2;
        if( $request->total_amount == $appointmentUpdate->payments->sum('amount')){
            $status = 1;
        }


        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_appointment);
        $request->request->add(["date_appointment" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $appointmentUpdate->update([
                'doctor_id' => $request->doctor_id,
                'date_appointment' => $request->date_appointment,
                'specialitie_id' => $request->specialitie_id,
                'doctor_schedule_join_hour_id' => $request->doctor_schedule_join_hour_id,
                'amount' => $request->total_amount,
                'status_pay' => $status
            ]);


        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteAppointment(string $id)
    {
        
        $appointmentDelete = Appointment::findOrFail($id);
        
        $this->authorize('delete', $appointmentDelete);
        //$appointmentPays = AppointmentPay::where('appointment_id', $id)->get();
        //foreach ($appointmentPays as $appointment) {
        //    $appointment->delete();
        //}
        $appointmentDelete->delete();
        return response()->json([
            'message' => 200
        ]);
    }
}
