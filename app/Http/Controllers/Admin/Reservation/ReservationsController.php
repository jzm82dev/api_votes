<?php

namespace App\Http\Controllers\Admin\Reservation;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Court\Court;
use Illuminate\Http\Request;
use App\Models\Court\Schedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Models\Reservation\Reservation;
use App\Http\Resources\Club\ClubResource;
use App\Models\Member\ClubUser;
use Illuminate\Support\Facades\Validator;
use App\Models\Reservation\ReservationHour;
use App\Models\Reservation\ReservationInfo;
use App\Models\Reservation\ReservationLessonDay;
use App\Models\Reservation\ReservationLessonDayHour;
use App\Models\Reservation\ReservationRecurrent;

class ReservationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function listRecurrents(Request $request){

        $this->authorize('viewAny', Reservation::class);

        $search = $request->search;
        $clubId = auth("api")->user()->club_id;
        
      
        $reservations = DB::table('reservation_recurrents')
                ->where('reservation_recurrents.deleted_at', NULL)
                ->where('reservation_recurrents.club_id', $clubId)
                ->join('reservations','reservations.reservation_recurrent_id', '=', 'reservation_recurrents.id')
                ->join('courts', 'reservations.court_id', '=', 'courts.id')
                ->select(
                    DB::raw("reservation_recurrents.id AS id"),
                    DB::raw("reservation_recurrents.club_id AS club_id"),
                    DB::raw("reservations.day_week_number AS day_week_number"),
                    DB::raw("reservation_recurrents.name AS reservation_owner_name"),
                    DB::raw("reservation_recurrents.mobile AS reservation_owner_mobile"),
                    DB::raw("reservations.start_time AS start_time"),
                    DB::raw("reservations.end_time AS end_time"),
                    DB::raw("courts.name AS court_name"),
                    DB::raw("courts.sport_type AS sport_type"),
                    DB::raw("reservation_recurrents.created_at AS created_at")
                )
                ->groupBy('id')
                ->get();

        return response()->json([
            "total" => $reservations->count(),
            "reservations" => $reservations
        ]);

    }

    public function getRecurrentReservation(Request $request){


        $this->authorize('update', Reservation::class);

        $clubId = auth("api")->user()->club_id;
        $id = $request->recurrent_id;
        
        $reservationData = ReservationRecurrent::findOrFail($id);

        if( $clubId != $reservationData->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED.."
            ], 403);
        }


        return response()->json([
            "message" => 200,
            "total" => 1,
            "name_reservation" => $reservationData->name,
            "mobile_reservation" => $reservationData->mobile,
            "date_end" => $reservationData->end_reservation,
            'reservations' => $reservationData->reservation->map(function($reservation) {
                return [
                    "id" => $reservation->id,
                    "date" => $reservation->date,
                    "start_time" => $reservation->start_time,
                    "end_time" => $reservation->end_time,
                    "court_id" => $reservation->court_id,
                    "court_name"=> $reservation->court->name
                ];
            })
        ]);

    }


    public function config( Request $request ){
        
        $this->authorize('viewReservationsToday', Reservation::class);

        $clubId = auth("api")->user()->club_id;

        $courtsBySport = Court::where('club_id', $clubId)->where('sport_type',$request->sport_selected )->get(['id', 'name']);
        //$courts = Court::where('club_id', $clubId)->get();
        $distinctSportCourt = Court::where('club_id', $clubId)->groupBy('sport_type')->get(); 
        
        
        $allOpenHours = [];
        $minHourOpen = [];
        $maxHourClose = [];



        $hasSpecialDay = DB::table('club_schedule_special_days')
                        ->where('club_schedule_special_days.deleted_at', NULL)
                        ->where('club_schedule_special_days.club_id', $clubId)
                        ->whereDate('club_schedule_special_days.date', $request->date)
                        ->leftJoin('club_schedule_special_day_hours', 'club_schedule_special_day_hours.club_schedule_special_day_id', '=', 'club_schedule_special_days.id')
                        ->where('club_schedule_special_day_hours.deleted_at', NULL)
                        ->select('club_schedule_special_day_hours.*')
                        ->orderBy('club_schedule_special_day_hours.opening_time_id', 'ASC')
                        ->get();

        if( count($hasSpecialDay) == 0){
            $openHours = DB::table('club_schedule_days')
                            ->where('club_schedule_days.deleted_at', NULL)
                            ->where('club_schedule_days.club_id', $clubId)
                            ->where('club_schedule_days.day_id', $request->day_week_number)
                            ->join('club_schedule_day_hours', 'club_schedule_day_hours.club_schedule_day_id', '=', 'club_schedule_days.id')
                            ->where('club_schedule_day_hours.deleted_at', NULL)
                            ->select('club_schedule_day_hours.*')
                            ->orderBy('club_schedule_day_hours.opening_time_id', 'ASC')
                            ->get();
        }else{
            if( isset($hasSpecialDay[0]->opening_time_id)){
                $openHours = $hasSpecialDay;
            }else{
                $openHours = [];
            }
            
        }


        foreach ($openHours as $openHour) {
            $openHourRange = self::getOpenHours($openHour);
            foreach( $openHourRange as  $hourId ){
                array_push($allOpenHours, $hourId);
            }
        }

                                   
        if(sizeof($openHours) > 0 ){
            $minHourOpen = $openHours[0];
            $maxHourClose = $openHours[ sizeof($openHours) -1 ] ;
        }

        $scheduleHours = collect([]);
        if($minHourOpen && $maxHourClose){                
            $scheduleHours = Schedule::where('id', '>=', $minHourOpen->opening_time_id)
                                    ->where('id', '<=', $maxHourClose->closing_time_id )
                                    ->orderBy('id')
                                    ->get();
        }

        

        $totalResevations = DB::table('reservations')
                ->where('reservations.deleted_at', NULL)
                ->where('reservations.club_id', $clubId)
                ->whereDate("reservations.date", Carbon::parse($request->date)->format("Y-m-d"))
                ->join('reservation_info', 'reservation_info.reservation_id', '=', 'reservations.id')
                ->join('reservation_hours', 'reservation_hours.reservation_id', '=', 'reservations.id')
                ->join('courts', 'courts.id', '=', 'reservations.court_id')
                ->where('courts.sport_type', '=', $request->sport_selected)
                ->select('reservations.id as reservation_id', 'reservation_hours.schedule_id as schedule_id', 
                            'reservations.court_id as court_id', 'reservation_info.name as reservation_name', 'reservation_info.online as is_online',
                            'courts.sport_type', 'reservations.start_time', 'reservations.end_time', 'reservations.type')
                ->orderBy('reservation_hours.schedule_id', 'ASC')
                ->get();

        

        $finalScheduleHours = collect([]);
        $finalScheduleHours = $scheduleHours;
        

        foreach ($finalScheduleHours as $item) {
            $item['courts'] = clone $courtsBySport;    
            //array_push($item['courts'], $courts);
        }

       


        return response()->json([
            'message' => 200,
            'total_types_sport' => $distinctSportCourt->count(),
            'all_courts' => $distinctSportCourt,
            'courts' => $courtsBySport,
            'finalScheduleHours' => $finalScheduleHours->map(function($hour_item) use($allOpenHours){
                return [
                    'courts' => $hour_item->courts,
                    'id' => $hour_item->id,
                    "hour_open" => in_array( $hour_item->id, $allOpenHours) ? true : false,
                    "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_start)->format("G:i"),
                    "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_end)->format("G:i")
                ];
            }),
            'reservations' => $totalResevations,
            'schedule_club' => $scheduleHours->map(function($hour_item) use($allOpenHours){
                return [
                    "id" => $hour_item->id,
                    "hour_start" => $hour_item->hour_start,
                    "hour_end" => $hour_item->hour_end,
                    "hour_open" => in_array( $hour_item->id, $allOpenHours) ? true : false,
                  //  "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_start)->format("G:i"),
                  //  "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_end)->format("G:i"),
                    "hour" => $hour_item->hour,
                   // "reservation_id" => null,
                   // "reservation_name" => ''
                ];
            })
        ]);    

    }

    public function getOpenHours( $range ){
        $openHours = collect([]);
        for ($i=$range->opening_time_id; $i <= $range->closing_time_id ; $i++) { 
            $openHours->push($i);
        }
        return $openHours;
    }

    public function configRecurrent(){
        $clubId = auth("api")->user()->club_id;
        Redis::del('club_profile_#'.$clubId);
        $cachedRecord = Redis::get('club_profile_#'.$clubId);
        $clubData = [];

        if(isset($cachedRecord)) {
            $clubData = json_decode($cachedRecord, FALSE);
        }else{
            $club =  Club::findOrFail($clubId);
            $clubData = [
                "message" => 200,
                "club_data" => ClubResource::make($club),
            ];
            Redis::set('club_profile_#'.$clubId, json_encode($clubData),'EX', 3600);
        }
        
        $hoursDay = collect([]);
        $courtScheduleHours = Schedule::all();
        foreach ($courtScheduleHours as $key => $scheduleHours) {
            $hoursDay->push([
                'id' => $scheduleHours->id,
                "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$scheduleHours->hour_start)->format("G:i"),
                "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$scheduleHours->hour_end)->format("G:i"),
            ]);
        }
        $clubData['schedule'] = $hoursDay;
        $nextDay = new Carbon('next tuesday'); 
        $clubData['test'] = $nextDay->toDateString();
        return response()->json($clubData);
    }


    public function saveRecurrent( Request $request){

        $this->authorize('create', Reservation::class);

        date_default_timezone_set('Europe/Madrid');
        $clubId = auth("api")->user()->club_id;
        $userId = auth("api")->user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'court_id' => 'required',
            'date_end_reservation' => 'required',
            'day_week_number' => 'required'
        ]);
        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       
        $court = Court::findOrFail($request->court_id);

        if( $clubId != $court->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }


        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_end_reservation);
        $endRecurrentReservationAxu = Carbon::parse($date_clean)->format("Y-m-d");
        $endRecurrentReservation = Carbon::createFromFormat('Y-m-d', $endRecurrentReservationAxu);
        $weekDay = self::getDaySelected($request->day_week_number);
        $auxDay = new Carbon('next '.$weekDay);

        $daySaved = collect([]);
        $dayCourtOccupied = collect([]);
        $changeCourtDay = collect([]);
        $request->request->add(['club_id' => $clubId]);
        //$request->request->add(['recurrent' => '1']);
        $request->request->add(['type' => 'recurrent']);
        $request->request->add(["created_by_user_id" => $userId]);
        $hoursSelected = json_decode($request->hours_selected, 1);

        if($auxDay->lt($endRecurrentReservation) ){
            $reservationRecurrent = ReservationRecurrent::create([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'club_id' => $clubId,
                'end_reservation' => $endRecurrentReservation
            ]);
            $request->request->add(['reservation_recurrent_id' => $reservationRecurrent->id]);
            
            if( $request->exists('member_id_reservation')){
                $request->request->add( ["user_id" => $request->member_id_reservation] );
            }
             
            $request->request->add(["type" => "recurrent"]);
            
            while ($auxDay->lt($endRecurrentReservation) ) {
                $dayReservation = $auxDay->toDateString();
                if( self::occupiedCourt($request->court_id, $hoursSelected, $dayReservation) == false){
                    $request->request->add(['date' => $dayReservation]);
                    // create reservation
                    $reservation = Reservation::create($request->all());
                    ReservationInfo::create([
                        'reservation_id' => $reservation->id,
                        'name' => $request->name,
                        'surname' =>  $request->exists('surname') ? $request->surname : null,
                        'mobile' => $request->mobile
                    ]);
                    
                    foreach ($hoursSelected as $key => $hour) {
                        $reservationHour = ReservationHour::create([
                            'reservation_id' => $reservation->id,
                            'start_time' => $hour['hour_start'],
                            'end_time' => $hour['hour_end'],
                            'schedule_id' => $hour['schedule_id']
                        ]);
                    }
                    $daySaved->push([
                        'date' => $dayReservation
                    ]);
                }else{
                    $reservationOtherCourt = self::saveRecurrentOtherCourt($clubId, $request, $request->court_id, $hoursSelected, $dayReservation);
                    if(false == $reservationOtherCourt){
                        $dayCourtOccupied->push([
                            'date' => $dayReservation
                        ]);
                    }else{
                        $changeCourtDay->push([
                            'date' => $dayReservation
                        ]);
                    }
                }
                $auxDay->addWeek();
            }

        }
        
        return response()->json([
            'message' => 200,
            'daysReserved' => $daySaved,
            'days_court_occupied' => $dayCourtOccupied,
            'days_court_changed' => $changeCourtDay
        ]);
    }

  
    public function updateRecurrent(Request $request, string $id){

        $this->authorize('updateRecurrent', Reservation::class);
        

        $clubId = auth("api")->user()->club_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
        ]);
        
        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $reservationRecurrent = ReservationRecurrent::where('id', $id)
            ->where('club_id', $clubId)
            ->first();

        
        if( $clubId != $reservationRecurrent->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }


        if($reservationRecurrent){
            $reservationRecurrent->update($request->all());
            DB::table('reservation_info')
                ->join('reservations', 'reservation_info.reservation_id', '=', 'reservations.id')
                ->where('reservations.reservation_recurrent_id', $reservationRecurrent->id)
                ->update([ 'name' => $request->name, 'mobile' => $request->mobile ]);
        }

        return response()->json([
            'message' => 200
        ]);
    }

    public function saveRecurrentOtherCourt($clubId, $request, $courtId, $hoursSelected, $dayReservation){
        $oldCourt = Court::findOrFail($courtId);

        $courts = Court::where('club_id', $clubId)
                        ->where('id', '<>', $courtId)
                        ->where('sport_type', '=', $oldCourt->sport_type)
                        ->get();
        $existFreeCourt = false;
        foreach($courts as $court) {
            if( self::occupiedCourt($court->id, $hoursSelected, $dayReservation) == false){
                $request->request->add(['date' => $dayReservation]);
                $originalCourt = $request->court_id;
                $request->request->add(['court_id' => $court->id]);
                // create reservation
                $reservation = Reservation::create($request->all());
                $request->request->add(['court_id' => $originalCourt]);
                ReservationInfo::create([
                    'reservation_id' => $reservation->id,
                    'name' => $request->name,
                    'surname' =>  $request->exists('surname') ? $request->surname : null,
                    'mobile' => $request->mobile
                ]);
                
                foreach ($hoursSelected as $key => $hour) {
                    $reservationHour = ReservationHour::create([
                        'reservation_id' => $reservation->id,
                        'start_time' => $hour['hour_start'],
                        'end_time' => $hour['hour_end'],
                        'schedule_id' => $hour['schedule_id']
                    ]);
                }
                $existFreeCourt = true;
                break;
            }
            
        }
        return $existFreeCourt;
    }


    public static function getDaySelected( $dayNumber ){
        $weekDay = [
            [
                'id' => 'day_1',
                'name' => 'monday'
            ],
            [
                'id' => 'day_2',
                'name' => 'tuesday'
            ],
            [
                'id' => 'day_3',
                'name' => 'wednesday'
            ],
            [
                'id' => 'day_4',
                'name' => 'thursday'
            ],
            [
                'id' => 'day_5',
                'name' => 'friday'
            ],
            [
                'id' => 'day_6',
                'name' => 'saturday'
            ],
            [
                'id' => 'day_7',
                'name' => 'sunday'
            ],
        ];

        foreach ( $weekDay as $element ) {
            if ( $dayNumber == $element['id'] ) {
                return $element['name'];
            }
        }
        
        return false;

    }


    public function occupiedCourt( $courtId, $hoursSelected, $date){

        $scheduleIDs = [];
        foreach ($hoursSelected as $key => $hour) {
            $scheduleIDs[] = $hour['schedule_id'];
        }

        $existsReservation = DB::table("reservations")
            ->where('reservations.deleted_at', NULL)
            ->whereDate('reservations.date', $date)
            ->where('reservations.court_id', intval($courtId))
            ->join("reservation_hours","reservations.id", "=", "reservation_hours.reservation_id")
            ->whereIn('reservation_hours.schedule_id', $scheduleIDs)//[1, 2, 3])
            ->count();//->dump();;
        if($existsReservation == 0){
            return false;
        }else{
            return true;
        }
        
    }


    public function deleteRecurrent( $id ){

        $this->authorize('deleteRecurrent', Reservation::class);

        $clubId = auth("api")->user()->club_id;
        
        $recurrentReserevations = ReservationRecurrent::where('id', $id)
            ->where('club_id', $clubId)
            ->first();
 
        if( $clubId != $recurrentReserevations->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }


        if( $recurrentReserevations ){
            foreach ($recurrentReserevations->reservation as $item) {
                $reservation = Reservation::findOrFail($item->id);
                $reservation->info->delete();
                $reservationsHours = $reservation->hours;
                foreach ($reservationsHours as $hour) {
                    $hour->delete();
                }
                $reservation->delete();
            }
            $recurrentReserevations->delete();
        }
        return response()->json([
            'message' => 200
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $clubId = auth("api")->user()->club_id;
        $userId = auth("api")->user()->id;

        $this->authorize('create', Reservation::class);

        $validator = Validator::make($request->all(), [
            'court_id' => 'required|integer',
            'name' => 'required|max:191',
            'mobile' => 'max:50',
            'date' => 'required',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $court = Court::findOrFail($request->court_id);

        if( $clubId != $court->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        $reservationDate = $request->date;
        $hoursSelected = json_decode($request->hours_selected, 1);
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        
        $dateAux = Carbon::parse($date_clean)->format("Y-m-d");

        $correctHours = self::correctlyHours($hoursSelected);
        if( $correctHours != ''){
            return response()->json([
                'message' => 422,
                'errors_text' => [$correctHours]
            ]);
        }

        if( true == self::isFreeCourtSelectedHour($clubId, $request->court_id,$dateAux, $hoursSelected )){
            return response()->json([
                'message' => 422,
                'errors_text' => ['La hora seleccionada ya no está disponible']
            ]);
        }

        if( false == self::isOpenClubSelectedHour($clubId, $request->day_week_number, $hoursSelected, $reservationDate )){
            return response()->json([
                'message' => 422,
                'errors_text' => ['El club está cerrado a la hora que quiere reservar']
            ]);
        }

        $request->request->add(['club_id' => $clubId]);
        $request->request->add(["created_by_user_id" => $userId]);
        
        if( $request->exists('member_id_reservation')){
            $request->request->add( ["user_id" => $request->member_id_reservation] );
        }

        $reservation = Reservation::create($request->all());

        ReservationInfo::create([
            'reservation_id' => $reservation->id,
            'name' => $request->name,
            'surname' =>  $request->exists('surname') ? $request->surname : null,
            'mobile' => $request->mobile
        ]);

        
        foreach ($hoursSelected as $key => $hour) {
            $reservationHour = ReservationHour::create([
                'reservation_id' => $reservation->id,
                'start_time' => $hour['hour_start'],
                'end_time' => $hour['hour_end'],
                'schedule_id' => $hour['schedule_id']
            ]);
        }

        return response()->json([
            'message' => 200,
            'reservation_id' => $reservation->id,
            'date' => $request->date
        ]);

    }


    public static function isFreeCourtSelectedHour($clubId, $courtId, $date, $hoursSelected){
        
        $resp = false;
        foreach ($hoursSelected as $hour) {
            $existsReservation = DB::table("reservations")
                ->where('reservations.club_id', $clubId)
                ->where('reservations.deleted_at', NULL)
                ->where('reservations.court_id', $courtId)
                ->whereDate('reservations.date', '=',$date)
                ->join("reservation_hours","reservations.id", "=", "reservation_hours.reservation_id")
                ->where('reservation_hours.deleted_at', NULL)
                ->where('schedule_id', $hour['schedule_id'])
                ->get();
            if( $existsReservation->count() > 0 ){
                $resp = true;
                break;
            }
        }
        return $resp;
    }

    public static function isOpenClubSelectedHour($clubId, $dayNumber, $hoursSelected, $date = ''){
        
        $resp = true;
             
        $hasSpecialDay = DB::table('club_schedule_special_days')
                ->where('club_schedule_special_days.deleted_at', NULL)
                ->where('club_schedule_special_days.club_id', $clubId)
                ->whereDate('club_schedule_special_days.date', $date)
                ->join('club_schedule_special_day_hours', 'club_schedule_special_day_hours.club_schedule_special_day_id', '=', 'club_schedule_special_days.id')
                ->where('club_schedule_special_day_hours.deleted_at', NULL)
                ->select('club_schedule_special_days.closed','club_schedule_special_day_hours.opening_time_id', 'club_schedule_special_day_hours.closing_time_id')
                ->orderBy('club_schedule_special_day_hours.opening_time_id', 'ASC')
                ->get();
                
        if( count($hasSpecialDay) == 0){
            $scheduleClubHours = DB::table("club_schedule_days")
                    ->where('club_schedule_days.deleted_at', NULL)
                    ->where('club_schedule_days.day_id', $dayNumber)
                    ->where('club_id', $clubId)
                    ->join("club_schedule_day_hours","club_schedule_day_hours.club_schedule_day_id", "=", "club_schedule_days.id")
                    ->where('club_schedule_day_hours.deleted_at', NULL)
                    ->select('club_schedule_days.closed','club_schedule_day_hours.opening_time_id', 'club_schedule_day_hours.closing_time_id')
                    ->get();
            }else{
                if( isset($hasSpecialDay[0]->opening_time_id)){
                    $scheduleClubHours = $hasSpecialDay;
                }else{
                    $scheduleClubHours = [];
                }
                
            }


        $openHourDay = [];
        if(sizeof($scheduleClubHours) > 0){
            foreach ($scheduleClubHours as $hour) {
                for ($i = $hour->opening_time_id; $i <= $hour->closing_time_id ; $i++) { 
                    $openHourDay[] = $i;
                }
            }
        }

        foreach ($hoursSelected as $hour) {
            if (in_array($hour['schedule_id'], $openHourDay) == false) {
                $resp = false;
                break;
            }
        }

        return $resp;
    }


    public static function correctlyHours($hoursSelected){
        $mesageError = '';
        if( count($hoursSelected) < 2){
            $mesageError = "La reserva mínima es de 1.5h";
        }elseif( count($hoursSelected) > 4){
            $mesageError = "La reserva máxima es de 2h";
        }else{
            $scheduleHours = [];
            foreach ($hoursSelected as $hour) {
                $scheduleHours[] = $hour['schedule_id'];
            }
            sort($scheduleHours);
            $arrlength = count($scheduleHours);
            for($i = 0; $i < $arrlength -1; $i++) {
                if( $scheduleHours[$i + 1] - $scheduleHours[$i] != 1){
                    $mesageError = 'Las horas de reservas tienen que ser consecutivas';
                    break;
                }
            }
        }
        return $mesageError;  
    }

    public function getResumePerMonth( Request $request ){
        
        $month = $request->month;
        $clubId = auth("api")->user()->club_id;
        $reservationPerMonth = DB::table("reservations")
                        ->where('reservations.deleted_at', NULL)
                        ->where('reservations.club_id', $clubId)
                        ->whereMonth('reservations.date', $month)
                        ->join("courts","reservations.court_id", "=", "courts.id")
                        ->select(
                            DB::raw("courts.name as court_name"),
                            DB::raw("COUNT(*) AS total_reservations"),
                            DB::raw("reservations.date as date")
                        )->groupBy("reservations.date", "reservations.court_id")
                        ->get();

         return response()->json([
            'message' => 200,
            'resservations' => $reservationPerMonth->map(function($reservation) {
                return [
                    //"id" => $appointment->id,
                    "title" => 'Pista '.$reservation->court_name.' ('.$reservation->total_reservations.')',
                    "date" => $reservation->date,
                    "display" => 'list-item',
                    "url" => '/reservations/new/'.$reservation->date
                ];
            })
        ]);
    }


    public function getResumePerRange( Request $request ){
        
        $clubId = auth("api")->user()->club_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $reservationPerMonth = DB::table("reservations")
                        ->where('reservations.deleted_at', NULL)
                        ->where('reservations.club_id', $clubId)
                        ->whereBetween('reservations.date', [$startDate, $endDate])
                        ->join("courts","reservations.court_id", "=", "courts.id")
                        ->select(
                            DB::raw("courts.name as court_name"),
                            DB::raw("COUNT(*) AS total_reservations"),
                            DB::raw("reservations.date as date")
                        )->groupBy("reservations.date", "reservations.court_id")
                        ->get();

         return response()->json([
            'message' => 200,
            'resservations' => $reservationPerMonth->map(function($reservation) {
                return [
                    //"id" => $appointment->id,
                    "title" => $reservation->court_name.' ('.$reservation->total_reservations.')',
                    "date" => $reservation->date,
                    "display" => 'list-item',
                    "url" => '/reservations/new/'.$reservation->date
                ];
            })
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       
        $this->authorize('viewReservationsToday', Reservation::class);

        $reservation = Reservation::findOrFail($id);
        $clubId = auth("api")->user()->club_id; 

        if( $clubId != $reservation->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        $clubUser = ClubUser::where('user_id', $reservation->user_id)->where('club_id', $reservation->club_id)->first();
        if( $clubUser){
            $reservation['name'] = $clubUser->name;
            $reservation['surname'] = $clubUser->surname;
        }else{
            $reservation['name'] = $reservation->info->name;
            $reservation['surname'] = $reservation->info->surname;
        }
        $reservation['mobile'] = $reservation->info->mobile;
        
        return response()->json( [
            'message' => 200,
            'reservation' => $reservation
        ]);
    }


    public function availableCourtReservation( $reservationId ){
        $reservation = Reservation::findOrFail($reservationId);
        $reservationHours = ReservationHour::where('reservation_id', $reservationId)->get();

        $availabresCourts = array(); 
        $courts = Court::where('club_id', $reservation->club_id)->where("court_id", "<>", $reservation->court_id)->get();
        foreach ($courts as $court) {
            # code...
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

        $reservation = Reservation::findOrFail($id);
        $this->authorize('delete', Reservation::class);

        $clubId = auth("api")->user()->club_id; 

        if( $clubId != $reservation->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }
     
        $reservation->info->delete();
        $reservationsHours = $reservation->hours;
        foreach ($reservationsHours as $hour) {
            $hour->delete();
        }
        $reservation->delete();



        return response()->json([
            'message' => 200
        ]);
    }
    
}
