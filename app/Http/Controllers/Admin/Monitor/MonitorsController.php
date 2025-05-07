<?php

namespace App\Http\Controllers\Admin\Monitor;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Court\Court;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Court\Schedule;
use App\Models\Monitor\Monitor;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Models\Reservation\Reservation;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\User\UserColletion;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Reservation\ReservationHour;
use App\Models\Reservation\ReservationInfo;
use App\Models\Reservation\ReservationLesson;
use App\Models\Reservation\ReservationLessonDay;
use App\Models\Reservation\ReservationLessonDayHour;
use Illuminate\Contracts\Queue\Monitor as QueueMonitor;
use App\Http\Controllers\Admin\Reservation\ReservationsController;

class MonitorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $this->authorize('viewAnyMonitor', User::class);

        $clubId = auth("api")->user()->club_id;
        $search = $request->search;
        $monitors = User::where(DB::raw("CONCAT(users.name, ' ',users.surname, ' ',users.email)") , 'like', '%'.$search.'%')->where('club_id', $clubId)
                     ->orderBy('id', 'desc')
                     ->whereHas("roles", function($q){
                        $q->where("name", "like", "%MONITOR%");
                     })
                     ->get();

        return response()->json([
            "monitor" => UserColletion::make($monitors)
        ]);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $this->authorize('createMonitor', User::class);
        
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50',
            'sport_type' => 'required|integer',
            'email' => 'email|required|max:191',
           // 'password' => 'required|max:191',
            'rol' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $clubId = auth("api")->user()->club_id;

        // SAVE DATA USER
        //$validMonitor = User::where('email', $request->email)->where('club_id', $clubId)->orWhere('mobile', 'like', '%'.$request->mobile.'%')->first();
        
        $validMonitor = User::where(function ($query) use($clubId){
            $query->where('club_id', $clubId);
        })->where(function ($query) use($request){
            $query->where('email', $request->email)
                  ->orWhere('mobile', $request->mobile);
        })->first();


        if($validMonitor){
            $errors[] = 'Ya existe un monitor con este email o teléfono';
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("monitors", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        //if($request->password){
            $request->request->add(['password' => 'monitor']); //bcrypt($request->password)]);
        //}

        
       
        $request->request->add(['club_id' => $clubId]);

        $user = User::create($request->all());
        
        $role = Role::findById( $request->rol);
        $user->assignRole($role);

        $courtsBySport = Court::where('club_id', $clubId)->where('sport_type', $user->sport_type)->get();

        return response()->json([
            'message' => 200,
            'monitor' => $user,
            'monitor_court' => $courtsBySport,
            'message_text' => 'Monitor saved correctly'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clubId = auth("api")->user()->club_id;
        $monitor = User::findOrFail($id);

        $this->authorize('viewMonitor', $monitor);

        $courtsBySport = Court::where('club_id', $clubId)->where('sport_type', $monitor->sport_type)->get();
        
        $lessons = collect();

        foreach ($monitor->lessons as $lesson_day) {
            $lessons->push([
                'id' => $lesson_day->id,
                'day_id' => $lesson_day->day_id,
                'day_name' => $lesson_day->day_name,
                'date_end_reservation' => $lesson_day->date_end_reservation,
                'hours' => $lesson_day->lesson_hours->map(function($hour_item){
                    return [
                        'id' => $hour_item->id,
                        'opening_time' => $hour_item->opening_time,
                        'opening_time_id' => $hour_item->opening_time_id,
                        'closing_time' => $hour_item->closing_time,
                        'closing_time_id' => $hour_item->closing_time_id,
                        'court_id' => $hour_item->court->id,
                        'court_name' => $hour_item->court->name,
                    ];
                })
            ]);
        }

        return response()->json( [
            'monitor' => UserResource::make($monitor),
            'monitor_court' => $courtsBySport,
            'lessons' => $lessons
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        $monitor = User::findOrFail($id);
        $this->authorize('editMonitor', $monitor);


        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50',
            'email' => 'email|required|max:191',
            'rol' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       /* $validMonitor = User::where('id', '<>', $id) 
                           ->where('mobile', $request->mobile)
                          // ->orWhere('email', $request->email)
                           ->first();
*/
        $validMonitor = User::where(function ($query) use($id){
                            $query->where('id', '<>', $id);
                        })->where(function ($query) use($request){
                            $query->where('email', $request->email)
                                  ->orWhere('mobile', $request->mobile);
                        })->first();

        if($validMonitor){
            $errors[] = 'Ya existe un monitor con este email o teléfono:'. $validMonitor->toSql();;
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

       
        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }


        if( $monitor->name != $request->name || $monitor->mobile != $request->mobile ){ 
            DB::table('reservation_info')
                ->join("reservations","reservations.id", "=", "reservation_info.reservation_id")
                ->where('reservations.user_id', $monitor->id)
                ->update([  
                    'reservation_info.name' => $request->name,
                    'reservation_info.mobile' => $request->mobile
                ]);
        }

        if($request->hasFile('imagen')){
            if( $monitor->avatar){
               $result = Storage::delete($monitor->avatar);
            }
            $path = Storage::putFile("monitors", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }
        

        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('doctor_profile_#'.$id);
        }

        $monitor->update($request->all());

        if( $request->rol != $monitor->roles()->first()->id){
            $roleOld = Role::findOrFail( $monitor->roles()->first()->id);
            $monitor->removeRole($roleOld);

            $roleNew = Role::findOrFail( $request->rol);
            $monitor->assignRole($roleNew);
        }


        return response()->json([
            'message' => 200,
            'monitor' => $monitor,
            'message_text' => 'Monitor saved correctly'
        ]);
    }



    public function config()
    {
        $this->authorize('createMonitor', User::class);

        $clubId = auth("api")->user()->club_id;
        $roles = Role::where('name', 'like', '%MONITOR%')->get();
        $courts = Court::where('club_id', $clubId)->get();
        
        return response()->json([
            "message" => 200,
            "roles" => $roles,
            "courts" => $courts
        ]);
    }

    

    public function saveLessons( Request $request){
        
        date_default_timezone_set('Europe/Madrid');
        $clubId = auth("api")->user()->club_id;
        $dayCourtOccupied = collect([]);
        $dayChangedCourt = collect([]);
        $allCourtChangeReservations = collect([]);
        $allNoExistAvailableCourtReservations = collect([]);
        $allDayLessonsReserved = collect([]);
       
        $validator = Validator::make($request->all(), [
            'monitor_id' => 'required|integer',
            'date_end_reservation' => 'required',
       //     'day_week_number' => 'required'
        ]);
        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $monitor = User::findOrFail($request->monitor_id);
        
        $this->authorize('editMonitor', $monitor);

        $scheduleWeeklyHours = json_decode($request->schedule_hour, 1);
        $schedule = Schedule::all();

        // DELETE CURRENT LESSON SCHEDULE  
        DB::table('reservation_lessons')->where('user_id', $request->monitor_id)->delete();
        DB::table('reservations')->where('user_id', $request->monitor_id)->where('type', 'class-lessons')->delete();

         // SAVE NEW SCHEDULE
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_end_reservation);
        $endLessonsReservationAxu = Carbon::parse($date_clean)->format("Y-m-d");
        $endLessonsReservation = Carbon::createFromFormat('Y-m-d', $endLessonsReservationAxu);

        $reservatonLesson = ReservationLesson::create([
            'club_id' => $clubId,
            'user_id' => $request->monitor_id,
            'sport_type' => $request->sport_type,
            'end_reservation' => $endLessonsReservation
        ]) ;


        foreach ($scheduleWeeklyHours as $key => $scheduleWeeklyHour) {
            if( sizeof($scheduleWeeklyHour['hours']) > 0){
                $reservationLessons = ReservationLessonDay::create([
                    'user_id' => $request->monitor_id,
                    'reservation_lesson_id' => $reservatonLesson->id,
                    'sport_type' => $request->sport_type,
                    'day_id' => $scheduleWeeklyHour['day_id'],
                    'day_name' => $scheduleWeeklyHour['day_name']
                ]);
                foreach ($scheduleWeeklyHour['hours'] as $hours) {
                    ReservationLessonDayHour::create([
                        'reservation_lesson_day_id' => $reservationLessons->id,
                        'court_id' => $hours['court_id'],
                        'opening_time' => $hours['opening_time'],
                        'closing_time' => $hours['closing_time'],
                        'opening_time_id' => $hours['opening_time_id'],
                        'closing_time_id' => $hours['closing_time_id']
                    ]);
                   list($dayReservedLessons, $existCourtChange, $noExistAvailableCourt) = self::createReservation($scheduleWeeklyHour['day_id'], $endLessonsReservation, $clubId, $monitor, $hours, $schedule);
                   $allDayLessonsReserved = $allDayLessonsReserved->merge($dayReservedLessons);
                   $allCourtChangeReservations = $allCourtChangeReservations->merge($existCourtChange);
                   $allNoExistAvailableCourtReservations = $allNoExistAvailableCourtReservations->merge($noExistAvailableCourt);
                }
            }else{
                ReservationLessonDay::create([
                    'user_id' => $request->monitor_id,
                    'reservation_lesson_id' =>$reservatonLesson->id,
                    'sport_type' => $request->sport_type,
                    'day_id' => $scheduleWeeklyHour['day_id'],
                    'day_name' => $scheduleWeeklyHour['day_name']
                ]);
            }    
        }

        return response()->json([
            'message' => 200,
            'daysReserved' => $allDayLessonsReserved,
            'days_court_changed' => $allCourtChangeReservations,
            'days_court_occupied' => $allNoExistAvailableCourtReservations
        ]);


    } 



    public function createReservation( $day_number, $endLessonsReservation, $clubId, $monitor, $dataReservation, $schedule ){

        $courtChangeReservations = array();
        $noExistAvailableCourtReservations = array();
        $dayReservedLessons = array();
        $weekDay = ReservationsController::getDaySelected($day_number);
        $auxDay = new Carbon('next '.$weekDay);
        $userId = auth("api")->user()->id;

        if($auxDay->lt($endLessonsReservation) ){
            while ($auxDay->lt($endLessonsReservation) ) {
                $dayReservation = $auxDay->toDateString();
                for($i = $dataReservation['opening_time_id']; $i<$dataReservation['closing_time_id']; $i++ ){
                    if( self::isOccupiedCourt($dataReservation['court_id'], $i, $dayReservation) == false){
                        $hourStart = Carbon::parse(date("Y-m-d ".$schedule[$i - 2]->hour_start))->format("H:i");
                        $hourEnd = Carbon::parse(date("Y-m-d ".$schedule[$i - 2]->hour_end))->format("H:i");
                        $reservation = Reservation::create([
                            'club_id' => $clubId,
                            'court_id' => $dataReservation['court_id'],
                            'user_id' => $monitor->id,
                            'type' => 'class-lessons',
                            'created_by_user_id' => $userId,
                            'date' => $dayReservation,
                            'day_week_number' => $day_number,
                            'start_time' => $hourStart,
                            'end_time' => $hourEnd
                        ]);
    
                        ReservationInfo::create([
                            'reservation_id' => $reservation->id,
                            'name' => $monitor->name,
                            'surname' => $monitor->surname,
                            'mobile' => $monitor->mobile
                        ]);

                        $reservationHour = ReservationHour::create([
                            'reservation_id' => $reservation->id,
                            'start_time' => $hourStart,
                            'end_time' => $hourEnd,
                            'schedule_id' => $i
                        ]);
                        $dayReservedLessons[] = $dayReservation ;
                    }else{
                        $reservationOtherCourt = self::saveLessonOtherCourt($day_number, $i, $dayReservation, $clubId, $monitor, $dataReservation['court_id']);
                        if( true == $reservationOtherCourt){
                            $courtChangeReservations[] = $dayReservation; // ha habido cambios en alguna pista
                        }else{
                            $noExistAvailableCourtReservations[] = $dayReservation; // Hay alguna reserva de clases que no se ha podido guardar
                        }
                    }
                }
                $auxDay->addWeek();
            }
            
        }

        return [array_unique($dayReservedLessons), array_unique($courtChangeReservations), array_unique($noExistAvailableCourtReservations)];
        
    }

    

    public function isOccupiedCourt($courtId, $scheduleId, $dateReservation){
        $existsReservation = DB::table("reservations")
                ->where('reservations.deleted_at', NULL)
                ->whereDate('reservations.date', $dateReservation)
                ->where('reservations.court_id', $courtId)
                ->join("reservation_hours","reservations.id", "=", "reservation_hours.reservation_id")
                ->where('reservation_hours.schedule_id', $scheduleId)
                ->count();
                
        if($existsReservation == 0){
            return false;
        }else{
            return true;
        } 
    }


    public function saveLessonOtherCourt($day_number, $scheduleId,$dayReservation, $clubId, $monitor, $courtId){
        
        $userId = auth("api")->user()->id;
        $oldCourt = Court::findOrFail($courtId);

        $schedule = Schedule::findOrFail($scheduleId);

        $otherCourts = Court::where('club_id', $clubId)
                ->where('id', '<>', $oldCourt->id)
                ->where('sport_type', '=', $oldCourt->sport_type)
                ->get();
        $existFreeCourt = false;
        foreach($otherCourts as $court) {
            if( self::isOccupiedCourt($court->id, $scheduleId, $dayReservation) == false){
                $hourStart = Carbon::parse(date("Y-m-d ".$schedule->hour_start))->format("h:i");
                $hourEnd = Carbon::parse(date("Y-m-d ".$schedule->hour_end))->format("h:i");
                $reservation = Reservation::create([
                    'club_id' => $clubId,
                    'court_id' => $court->id,
                    'user_id' => $monitor->id,
                    'type' => 'class-lessons',
                    'created_by_user_id' => $userId,
                    'date' => $dayReservation,
                    'day_week_number' => $day_number,
                    'start_time' => $hourStart,
                    'end_time' => $hourEnd
                ]);

                ReservationInfo::create([
                    'reservation_id' => $reservation->id,
                    'name' => $monitor->name,
                    'surname' => $monitor->surname,
                    'mobile' => $monitor->mobile
                ]);

                $reservationHour = ReservationHour::create([
                    'reservation_id' => $reservation->id,
                    'start_time' => $hourStart,
                    'end_time' => $hourEnd,
                    'schedule_id' => $scheduleId
                ]);
                $existFreeCourt = true;
                break;
            }
        }
        return $existFreeCourt;
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function deleteMonitor(string $id)
    {
       
        $monitor = User::findOrFail($id);
        $this->authorize('deleteMonitor', $monitor);     
       
        if( $monitor->avatar){
            Storage::delete($monitor->avatar);
        }
        $role = Role::findOrFail( $monitor->roles()->first()->id);
        $monitor->removeRole($role);

        $cachedRecord = Redis::get('doctor_profile_#'.$id);
        if(isset($cachedRecord)) {
            Redis::del('doctor_profile_#'.$id);
        }
        
         // DELETE CURRENT LESSON SCHEDULE  
         DB::table('reservation_lessons')->where('user_id', $id)->delete();
         DB::table('reservations')->where('user_id', $id)->where('type', 'class-lessons')->delete();

        $monitor->delete();
        
        return response()->json([
            'message' => 200
        ]);
    }

    


}
