<?php

namespace App\Http\Controllers\Admin\Doctor;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\Doctor\Specialities;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Facades\Storage;
use App\Models\Doctor\DoctorScheduleDay;
use App\Http\Resources\User\UserResource;
use App\Models\Doctor\DoctorScheduleHour;
use App\Http\Resources\User\UserColletion;
use App\Models\Doctor\DoctorScheduleJoinHour;
use App\Http\Resources\Appointment\AppointmentCollection;

class DoctorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $this->authorize('viewAnyDoctor', Doctor::class);

        $search = $request->search;
        $doctors = User::where(DB::raw("CONCAT(users.name, ' ',users.surname, ' ',users.email)") , 'like', '%'.$search.'%')
                    //where('name', 'like', '%'.$search.'%')
                     //->orWhere('surname', 'like', '%'.$search.'%')
                     //->orWhere('email', 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     ->whereHas("roles", function($q){
                        $q->where("name", "like", "%DOCTOR%");
                     })
                     ->get();

        return response()->json([
            "user" => UserColletion::make($doctors)
        ]);
    }


    public function profile(string $id){
        
        $this->authorize('profileDoctor', Doctor::class);

        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        $doctorData = [];

        if(isset($cachedRecord)) {
            $doctorData = json_decode($cachedRecord, FALSE);
        }else{
            $doctor = User::findOrFail($id);
            $totalAppointments = Appointment::where("doctor_id", $id)->count();
            $moneyAppointments = Appointment::where("doctor_id", $id)->sum("amount");
            $totalPendingAppointments = Appointment::where("doctor_id", $id)->where("status",1)->count();
            $pendingAppointments = Appointment::where("doctor_id", $id)->where("status",1)->get();
            $appointments = Appointment::where("doctor_id", $id)->orderBy('date_appointment', 'asc')->get();

            $doctorData = [
                "message" => 200,
                "doctor" => UserResource::make($doctor),
                "appointments" => $appointments->map(function($item){
                    return [
                        "id" => $item->id,
                        "patient" => [
                            "id" => $item->patient->id,
                            "fullname" => $item->patient->name.' '.$item->patient->surname
                        ],
                        "doctor" => [
                            "id" => $item->doctor->id,
                            "fullname" => $item->doctor->name.' '.$item->doctor->surname, 
                            'avatar' => 'https://cdn-icons-png.flaticon.com/512/1430/1430453.png'
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
                        'total_paid' => $item->payments->sum("amount"),
                        'status' => $item->status, 
                    ];
                }),
                "pending_appointments" => AppointmentCollection::make($pendingAppointments),
                "total_appointment" => $totalAppointments,
                "total_pending_appointments" => $totalPendingAppointments,
                "pending_appointments" => $pendingAppointments,
                "total_money" => $moneyAppointments
            ];
            Redis::set('doctor_profile_#'.$id, json_encode($doctorData),'EX', 3600);
        }

        return response()->json($doctorData);
    }


    public function updateProfile(Request $request, string $id){

        $this->authorize('profileDoctor', Doctor::class);

        $validDoctor = User::where('email', $request->email)
                           ->where('id', '<>', $id) 
                           ->first();

        if($validDoctor){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un doctor con este email'
            ]);
        }

        $doctor = User::findOrFail($id);
        
        /*
        $currentPassword = $request->cuurent_password;
        if($request->password){
            if( bcrypt($currentPassword) != $doctor->password ){
                return response()->json([
                    'message' => 403,
                    'message_text' => 'La contraseña antigua no coincide con la almacenada en nuestro sistemas',
                    'old_pass' => $doctor->password,
                    'ssss' => $currentPassword,
                    'currentPassword' => bcrypt('zancada'),
                    'currentPassword_encript' => bcrypt($currentPassword)
                ]);
            }
            $request->request->add(['password' => bcrypt($request->password)]);
        }
        */

        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('doctor_profile_#'.$id);
        }

        $doctor->update($request->all());

        return response()->json([
            'message' => 200,
            'message_text' => 'Doctor actualizado correctamente'
        ]);

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('createDoctor', Doctor::class);

        // SAVE DATA USER
        $validUser = User::where('email', $request->email)->first();

        if($validUser){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un usuario con este email'
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("staffs", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birthday);
        $request->request->add(["birthday" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $user = User::create($request->all());
        
        $role = Role::findById( $request->rol);
        $user->assignRole($role);
        
        // SAVE DATA SCHEDULE
        $scheduleHours = json_decode($request->schedule_hour, 1);
        foreach ($scheduleHours as $key => $scheduleHour) {
            if( sizeof($scheduleHour['children']) > 0){
                $doctorScheduleDay = DoctorScheduleDay::create([
                    'user_id' => $user->id,
                    'day' => $scheduleHour['day_name']
                ]);
                foreach ($scheduleHour['children'] as $children) {
                    DoctorScheduleJoinHour::create([
                        'doctor_schedule_day_id' => $doctorScheduleDay->id,
                        'doctor_schedule_hour_id' => $children['id']
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 200,
            'user' => $user,
            'message_text' => 'Doctor Almacenado Correctamente'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('editDoctor', Doctor::class);
        
        $user = User::findOrFail($id);
        
        return response()->json( [
            'doctor' => UserResource::make($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('updateDoctor', Doctor::class);

        $validDoctor = User::where('email', $request->email)
                           ->where('id', '<>', $id) 
                           ->first();

        if($validDoctor){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un usuario con este email'
            ]);
        }

       
        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $doctor = User::findOrFail($id);

        if($request->hasFile('imagen')){
            if( $doctor->avatar){
                Storage::delete($doctor->avatar);
            }
            $path = Storage::putFile("staffs", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }
        
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birthday);
        $request->request->add(["birthday" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);


        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('doctor_profile_#'.$id);
        }

        $doctor->update($request->all());

        if( $request->rol != $doctor->roles()->first()->id){
            $roleOld = Role::findOrFail( $doctor->roles()->first()->id);
            $doctor->removeRole($roleOld);

            $roleNew = Role::findOrFail( $request->rol);
            $doctor->assignRole($roleNew);
        }

        // Borramos los horarios que tiene actualmente para insertar los nuevos
        /* Ahora en casacada */
            $days = $doctor->schedule_days;
            foreach ($days as $key => $sheduleDay) {
                $sheduleDay->delete();
            }
        
        /*
        $oldScheduleDay = DoctorScheduleDay::where('user_id', $doctor->id)->get();
        foreach ($oldScheduleDay as $day) {
            foreach ($day->schedules_hours as $schedules_hour) {
                $schedules_hour->delete();
            }
            $day->delete();
        } 
        */  

        // Insertamos los horarios nuevos
        $scheduleHours = json_decode($request->schedule_hour, 1);
        foreach ($scheduleHours as $key => $scheduleHour) {
            if( sizeof($scheduleHour['children']) > 0){
                $doctorScheduleDay = DoctorScheduleDay::create([
                    'user_id' => $doctor->id,
                    'day' => $scheduleHour['day_name']
                ]);
                foreach ($scheduleHour['children'] as $children) {
                    DoctorScheduleJoinHour::create([
                        'doctor_schedule_day_id' => $doctorScheduleDay->id,
                        'doctor_schedule_hour_id' => $children['id']
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 200,
            'user' => $doctor,
            'message_text' => 'Doctor Almacenado Correctamente'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteDoctor(string $id)
    {
        $this->authorize('deleteDoctor', Doctor::class);

        $doctor = User::findOrFail($id);
        foreach ($doctor->schedule_days as $schedule_day) {
            $schedule_day->delete();
        }
        if( $doctor->avatar){
            Storage::delete($doctor->avatar);
        }
        $role = Role::findOrFail( $doctor->roles()->first()->id);
        $doctor->removeRole($role);

        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('doctor_profile_#'.$id);
        }
        
        $doctor->delete();
        
        return response()->json([
            'message' => 200
        ]);
    }

    public function config()
    {
        $roles = Role::where('name', 'like', '%DOCTOR%')->get();
        $specialities = Specialities::where('state', 1)->get();
        $hoursDay = collect([]);

        $doctorScheduleHours = DoctorScheduleHour::all();
        foreach ($doctorScheduleHours->groupBy("hour") as $key => $scheduleHours) {
            $hoursDay->push([
                'hour' => $key,
                "format_hour" =>Carbon::parse(date("Y-m-d ".$key.':i:s'))->format("h A"),
                'items' => $scheduleHours->map(function($hour_item){
                    // Y-m-d h:i:s 2023-10-25 00:30:51
                    return [
                        "id" => $hour_item->id,
                        "hour_start" => $hour_item->hour_start,
                        "hour_end" => $hour_item->hour_end,
                        "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_start)->format("h:i A"),
                        "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_end)->format("h:i A"),
                        "hour" => $hour_item->hour,
                    ];
                })
            ]);
        }
        //dd($hoursDay);
        //dd($doctorScheduleHours->groupBy("hour"));

        return response()->json([
            "message" => 200,
            "roles" => $roles,
            "specialities" => $specialities,
            "hours_days" => $hoursDay
        ]);
    }

}
