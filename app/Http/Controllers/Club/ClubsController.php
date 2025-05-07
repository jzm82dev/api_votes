<?php

namespace App\Http\Controllers\Club;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Club\Club;
use Illuminate\Http\Request;
use App\Models\Location\City;
use App\Models\Court\Schedule;
use App\Models\Location\State;
use App\Models\Member\ClubUser;
use App\Models\Club\ClubService;
use App\Models\Location\Country;
use App\Http\Requests\ClubRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Club\ClubScheduleDay;
use Illuminate\Support\Facades\Redis;
use App\Models\Reservation\Reservation;
use Illuminate\Support\Facades\Storage;
use App\Models\Club\ClubScheduleDayHour;
use App\Http\Resources\Club\ClubResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Club\ClubColletion;
use App\Models\Club\ClubAdditionalInformation;
use App\Models\Club\ClubScheduleSpecialDay;
use App\Models\Club\ClubScheduleSpecialDayHour;
use App\Models\Club\ClubSocialLink;

class ClubsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
        $this->authorize('viewAny', Club::class);
        $search = $request->search;
        
        $clubs = Club::where(DB::raw("CONCAT(clubs.name, ' ',IFNULL(clubs.club_manager, ''), ' ',clubs.email)") , 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     ->paginate(20);

        return response()->json([
            "total" => $clubs->total(),
            "clubs" => ClubColletion::make($clubs)
        ]);
       
    }


    
    public function config()
    {
        $this->authorize('viewAny', Club::class);

        $hoursDay = collect([]);

        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $courtScheduleHours = Schedule::all();
        foreach ($courtScheduleHours as $key => $scheduleHours) {
            $hoursDay->push([
                'id' => $scheduleHours->id,
                "format_hour" =>Carbon::parse(date("Y-m-d").' '.$scheduleHours->hour_start)->format("G:i"),
            ]);
        }

        return response()->json([
            "message" => 200,
            "hours_days" => $hoursDay,
            "courts" => $club->courts
        ]);
    }

    public function pendingMembers()
    {
        $this->authorize('viewAny', Club::class);
        $clubId = auth("api")->user()->club_id;

        $clubUsers = ClubUser::where('status', 'PENDING')->where('club_id', $clubId)->get();

        return response()->json([
            "pending_members" => $clubUsers->map(function($clubUser){
                return [
                    "club_user_id" => $clubUser->id,
                    "user_id" => $clubUser->user_id,
                    "name" => $clubUser->user->name,
                    "surname" => $clubUser->user->surname,
                    "date_joined" => $clubUser->created_at,
                    "mobile" => $clubUser->user->mobile,
                    "email" => $clubUser->user->email,
                    "photo" => $clubUser->user->avatar ? env("APP_URL")."storage/". $clubUser->user->avatar : 'assets/img/user.jpg'
                ];
            }),
        ]);

    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClubRequest $request)
    {
    
        $this->authorize('create', Club::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }


        $validClub = Club::where('name', $request->name)
                        ->first();

        if($validClub){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un club con este nombre y/o email'
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("clubs", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        $hash = str()->random(5);
        
        $request->request->add(['hash' => $hash]);
        $club = Club::create($request->all());
        
        
        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('viewAny', Club::class);

        $club = Club::findOrFail($id);
        
        return response()->json( [
            'club' => ClubResource::make($club)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateData(Request $request)
    {

        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $this->authorize('update', $club);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        $existClub = DB::table("clubs")
            ->where('clubs.deleted_at', NULL)
            ->where('clubs.id', '<>', $clubId)
            ->where('name', $request->name)
            ->join("club_additional_informations","club_additional_informations.club_id", "=", "clubs.id")
            ->where("club_additional_informations.city_id", $club->additional_information->city_id)
            ->where('club_additional_informations.deleted_at', NULL)
            ->count('*');
        
       
        if($existClub > 0){
            $errors[] = 'Ya existe un club con este nombre';
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }
        
        $club = Club::findOrFail($clubId);
        
        if($request->hasFile('imagen')){
            if( $club->avatar){
               $result = Storage::delete($club->avatar);
            }
            $path = Storage::putFile("clubs", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }

        $cachedRecord = Redis::get('club_profile_#'.$clubId);
        if(isset($cachedRecord)) {
            Redis::del('club_profile_#'.$clubId);
        }

        $club->update($request->all());

        return response()->json([
            'message' => 200,
            'club' => $club
        ]);

    }


    public function updateDataDescription(Request $request){
        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $this->authorize('update', $club);
        $additionalData = ClubAdditionalInformation::where('club_id', $clubId)->first();

        $additionalData->update($request->all());

        return response()->json([
            'message' => 200,
            'description' => $request->description
        ]);
    }

    public function updateAdditionalData(Request $request)
    {
        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $validator = Validator::make($request->all(), [
            'address' => 'required|max:191',
            'additional_address' => 'max:191',
            'postal_code' => 'required|max:191',
            'country_id' => 'required|integer',
            'city_id' => 'required|integer'
        ]);

        if($validator->fails()){
           $errors = get_errors($validator->errors());
           
           return response()->json([
                'message' => 422,
                'errors_text' => $errors,
            ]);
        }
        
        $additionalData = ClubAdditionalInformation::where('club_id', $clubId)->first();

        if( $additionalData){
            $additionalData->update($request->all());
        }else{
            $request->request->add(['club_id' => $clubId]);
            ClubAdditionalInformation::create($request->all());
        }

        Redis::del('club_profile_#'.$clubId);
        
        return response()->json([
            'message' => 200
        ]);
        
    }
    
    public function descriptionData(){
        $clubId = auth("api")->user()->club_id;
        $club =  Club::findOrFail($clubId);

        $description = $club->additional_information->description;
        
        return response()->json([
            'message' => 200,
            'description' => $description
        ]);
    }

    public function profileData(){

        $this->authorize('viewAny', Club::class);

        $clubId = auth("api")->user()->club_id;
        $cachedRecord = Redis::get('club_profile_#'.$clubId);
        $clubData = [];

        $countries = Country::all(['id', 'name']);

        if(isset($cachedRecord)) {
            $clubData = json_decode($cachedRecord, FALSE);
        }else{
            $club =  Club::findOrFail($clubId);
            $states = State::where('country_id', $club->additional_information->country_id)->orderBy('name', 'asc')->get(['id', 'name']);
            $cities = City::where('state_id', $club->additional_information->state_id)->orderBy('name', 'asc')->get(['id', 'name']);

            $data = [
                'id' =>  $club->id, 
                'cif' => $club->cif,
                'name' =>  $club->name, 
                'manager' =>  $club->club_manager, 
                'email' =>  $club->email,
                'mobile' =>  $club->mobile,
                'users_can_book' => $club->users_can_book,
                'additional_info' =>  $club->additional_information,
                'avatar' =>  $club->avatar ? env("APP_URL")."storage/". $club->avatar : '',
                'created_at' =>  $club->created_at->format("Y-m-d h:i:A")
            ];
            $clubData = [
                "message" => 200,
                "club_data" => $data,
                "countries" => $countries,
                "states" => $states,
                "cities" => $cities
            ];
            Redis::set('club_profile_#'.$clubId, json_encode($clubData),'EX', 3600);
        }

        return response()->json($clubData);
       
    }


    public function scheduleData(){

        $this->authorize('viewAny', Club::class);

        $clubId = auth("api")->user()->club_id;

        $cachedRecord = Redis::get('club_schedule_#'.$clubId);
        $clubData = [];

        if(isset($cachedRecord)) {
            $clubData = json_decode($cachedRecord, FALSE);
        }else{
            $club =  Club::findOrFail($clubId);
            $clubData = [
                "message" => 200,
                "club_data" => ClubResource::make($club),
            ];
            Redis::set('club_schedule_#'.$clubId, json_encode($clubData),'EX', 3600);
        }

        return response()->json($clubData);
       
    }


    public function saveSpecialDay( Request $request){

        $clubId = auth("api")->user()->club_id;
        $club =  Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $scheduleSpecialHours = json_decode($request->schedule_hour, 1);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $date = Carbon::parse($date_clean)->format("Y-m-d");

        $existSpecialDay = ClubScheduleSpecialDay::where('club_id', $clubId)
            ->whereDate('date', $date)->exists();
        if( $existSpecialDay){
            return response()->json([
                'message' => 420,
                'errors_text' => 'Día repetido',
            ]);
        }

        $specialDay = ClubScheduleSpecialDay::create([
            'club_id' => $club->id,
            'date' => $date,
            'information' => $request['information'],
            'closed' => $request['closed']
        ]);

        foreach ($scheduleSpecialHours as $specialDayHours) {
            ClubScheduleSpecialDayHour::create([
                'club_schedule_special_day_id' => $specialDay->id,
                'opening_time' => $specialDayHours['opening_time'],
                'closing_time' => $specialDayHours['closing_time'],
                'opening_time_id' => $specialDayHours['opening_time_id'],
                'closing_time_id' => $specialDayHours['closing_time_id']
            ]);
        }
        
        Redis::del('club_schedule_#'.$clubId);

        $specialDay['hours'] = $specialDay->schedulesSpecialDayHours->map(function($hour_item){
            return [
                'id' => $hour_item->id,
                'opening_time' => $hour_item->opening_time,
                'opening_time_id' => $hour_item->opening_time_id,
                'closing_time' => $hour_item->closing_time,
                'closing_time_id' => $hour_item->closing_time_id
            ];
        }); 

        return response()->json([
            'message' => 200,
            'special_day' => $specialDay
        ]);

    }


    public function removeSpecialDay(string $id){

        $clubId = auth("api")->user()->club_id;
        $club =  Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $specialDay = ClubScheduleSpecialDay::findOrFail($id);

        $specialDayHours = ClubScheduleSpecialDayHour::where('club_schedule_special_day_id', '=', $specialDay->id)->delete();

        $specialDay->delete();
        Redis::del('club_schedule_#'.$clubId);
        
        return response()->json([
            'message' => 200,
            'message_text' => 'Special day deleted'
        ]);

    }


    public function updateWeekly(Request $request ){

        $clubId = auth("api")->user()->club_id;
        $club =  Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $scheduleWeeklyHours = json_decode($request->schedule_hour, 1);

        //Check if is not possible change de schedule club because there are any reservations old hour
        $exitsReservations = self::existReservationOldHours($scheduleWeeklyHours);
        if($exitsReservations->count() > 0 ){
            return response()->json([
                'message' => 422,
                //'reservations' => $reservations,
                'errors_text' => $exitsReservations//['Existen reservas no compatible con el nuevo horario']
            ]);
        }

        // DELETE CURRENT SCHEDULE  
        DB::table('club_schedule_days')->where('club_id', $clubId)->delete();
        
        Redis::del('club_schedule_#'.$clubId);

        // SAVE NEW SCHEDULE
        foreach ($scheduleWeeklyHours as $key => $scheduleWeeklyHour) {
            if($scheduleWeeklyHour['closed'] || sizeof($scheduleWeeklyHour['hours']) == 0){
                ClubScheduleDay::create([
                    'club_id' => $club->id,
                    'day_id' => $scheduleWeeklyHour['day_id'],
                    'day_name' => $scheduleWeeklyHour['day_name'],
                    'closed' => $scheduleWeeklyHour['closed']
                ]);
            }else{
                if( sizeof($scheduleWeeklyHour['hours']) > 0){
                    $clubSchedule = ClubScheduleDay::create([
                        'club_id' => $club->id,
                        'day_id' => $scheduleWeeklyHour['day_id'],
                        'day_name' => $scheduleWeeklyHour['day_name'],
                        'closed' => $scheduleWeeklyHour['closed'],
                    ]);
                    foreach ($scheduleWeeklyHour['hours'] as $hours) {
                        ClubScheduleDayHour::create([
                            'club_schedule_day_id' => $clubSchedule->id,
                            'opening_time' => $hours['opening_time'],
                            'closing_time' => $hours['closing_time'],
                            'opening_time_id' => $hours['opening_time_id'],
                            'closing_time_id' => $hours['closing_time_id']
                        ]);
                    }
                }
            }
        }
        return response()->json([
            'message' => 200
        ]);
    }


    public static function existReservationOldHours( $scheduleWeeklyHours ){

        $clubId = auth("api")->user()->club_id;
        
        $dayReservations = collect([]);        
        foreach ($scheduleWeeklyHours as $key => $scheduleWeeklyHour) {
            $scheduleOpenDay = [];
            if($scheduleWeeklyHour['closed']){
                $scheduleWeeklyHour['hours'] = [];
            }
            if(sizeof($scheduleWeeklyHour['hours']) > 0){
                foreach ($scheduleWeeklyHour['hours'] as $hour) {
                    for ($i = $hour['opening_time_id']; $i <= $hour['closing_time_id'] ; $i++) { 
                        $scheduleOpenDay[] = $i;
                    }
                }
            }
           
            $existsReservation = DB::table("reservations")
                ->where('reservations.deleted_at', NULL)
                ->where('reservations.club_id', $clubId)
                ->whereDate('reservations.date', '>=', now()->format("Y-m-d"))
                ->where('reservations.day_week_number', '=', $scheduleWeeklyHour['day_id'])
                ->join("reservation_hours","reservations.id", "=", "reservation_hours.reservation_id")
                ->where('reservation_hours.deleted_at', NULL)
                ->whereNotIn("reservation_hours.schedule_id", $scheduleOpenDay)
                ->select('reservations.date', 'reservations.start_time', 'reservations.end_time')
                ->groupBy("reservations.id")
                ->get();
            if( $existsReservation->count() > 0 ){
                foreach ($existsReservation as $reservation) {
                    $date = Carbon::parse($reservation->date)->format("d/m/Y");
                    $dayReservations->push($date.' ('.$reservation->start_time.'-'.$reservation->end_time.'h)');
                }
            }
        }
        return $dayReservations;
    }



    public function getServices(){
        
        $this->authorize('viewAny', Club::class);

        $clubId = auth("api")->user()->club_id;
        $cachedRecord = Redis::get('club_services_#'.$clubId);
        $clubData = [];

        if(isset($cachedRecord)) {
            $clubData = json_decode($cachedRecord, FALSE);
        }else{
            $club =  Club::findOrFail($clubId);
            if( $club->services){
                $clubData = [
                    "message" => 200,
                    "club_services" => $club->services,
                    "more_services" => $club->services->more_services ? json_decode($club->services->more_services) : []
                ];
            }else{
                $clubData = [
                    "message" => 200,
                    "club_services" => NULL,
                    "more_services" => []
                ];
            }
            Redis::set('club_services_#'.$clubId, json_encode($clubData),'EX', 3600);
        }

        return response()->json($clubData);
       
    }

    public function storeServices( Request $request ){

        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $servicesData = ClubService::where('club_id', $clubId)->first();
        
        $request->request->add(['more_services'=> $request->more_services]);

        if( $servicesData){
            $servicesData->update($request->all());
        }else{
            $request->request->add(['club_id' => $clubId]);
            ClubService::create($request->all());
        }
        Redis::del('club_services_#'.$clubId);
        
        return response()->json([
            'message' => 200
        ]);
        
    }


    public function getSocialLinks(){
        
        $this->authorize('viewAny', Club::class);

        $clubId = auth("api")->user()->club_id;
        $cachedRecord = Redis::get('club_social_links_#'.$clubId);
        $clubData = [];

        if(isset($cachedRecord)) {
            $clubData = json_decode($cachedRecord, FALSE);
        }else{
            $club =  Club::findOrFail($clubId);
            if( $club->social_links){
                $clubData = [
                    "message" => 200,
                    "club_social_links" => $club->social_links
                ];
            }else{
                $clubData = [
                    "message" => 200,
                    "club_social_links" =>[]
                ];
            }
            Redis::set('club_social_links_#'.$clubId, json_encode($clubData),'EX', 3600);
        }

        return response()->json($clubData);
       
    }

    public function storeSocialLinks( Request $request ){

        $clubId = auth("api")->user()->club_id;
        $club = Club::findOrFail($clubId);

        $this->authorize('update', $club);

        $clubSocialLink = ClubSocialLink::where('club_id', $clubId)->first();

        if( $clubSocialLink){
            $clubSocialLink->update($request->all());
        }else{
            $request->request->add(['club_id' => $clubId]);
            ClubSocialLink::create($request->all());
        }
        Redis::del('club_social_links_#'.$clubId);
        
        return response()->json([
            'message' => 200
        ]);
        
    }

   

    public static function getStates( string $id){

        $states = State::where('country_id', $id)->orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json([
            'message' => 200,
            'country_id' => $id,
            'states' => $states
        ]);
    }

    public static function getCities( string $id){

        $states = City::where('state_id', $id)->orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json([
            'message' => 200,
            'country_id' => $id,
            'cities' => $states
        ]);
    }
    


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $club = Club::findOrFail($id);
        
        $this->authorize('delete', $club);
        
        if( $club->avatar){
            Storage::delete($club->avatar);
        }

        $club->delete();

        return response()->json([
            'message' => 200
        ]);

    }
}
