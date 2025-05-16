<?php

namespace App\Http\Controllers\Club;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\SendEmail;
use App\Models\Club\Club;
use App\Models\Court\Court;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Couple\Couple;
use App\Models\League\League;
use App\Models\Location\City;
use App\Models\Court\Schedule;
use App\Models\Journey\Journey;
use App\Models\Member\ClubUser;
use App\Mail\NotificationAppoint;
use App\Models\Category\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CouplePlayer;
use App\Models\Couple\CoupleResult;
use App\Http\Controllers\Controller;
use App\Models\Journey\JourneyMatch;
use Illuminate\Support\Facades\Mail;
use App\Models\Tournament\Tournament;
use function PHPUnit\Framework\isNull;
use App\Models\Reservation\Reservation;
use App\Models\User\PasswordResetToken;
use App\Http\Resources\Club\ClubResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Club\ClubColletion;
use App\Models\Tournament\TournamentMatch;
use App\Models\Reservation\ReservationHour;

use App\Models\Reservation\ReservationInfo;
use App\Http\Resources\Couple\CoupleCollection;
use App\Http\Controllers\League\LeaguesController;
use App\Http\Controllers\Journey\JourneyController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Tournament\TournamentsController;
use App\Http\Controllers\Admin\Reservation\ReservationsController;
use App\Http\Resources\Urbanisation\UrbanisationCollection;
use App\Models\Club\ConfirmEmail;
use App\Models\Meeting\Meeting;
use App\Models\Meeting\Question;
use App\Models\Meeting\Vote;
use App\Models\Urbanisation\Urbanisation;

class PublicDataController extends Controller
{

    public function searchClubs(Request $request){

        $clubs = Club::where ('name', 'like', '%'.$request->search.'%')
                     ->whereNotNull('club_verified_at')
                     ->orderBy('id', 'asc')
                     ->get();
        $clubs = ClubColletion::make($clubs);
        $clubs->map(function($club){
            if( strlen($club->additional_information->description) > 300){
                $club->additional_information->description = substr($club->additional_information->description, 0, 300).' ...';
            }
        });

              
        return response()->json([
            "total" => count($clubs),
            "clubs" => $clubs,
        ]);
    }


     public function getUrbanizations(Request $request){

        $search = $request->search;
        
        $urbanisations = Urbanisation::where(DB::raw("CONCAT(urbanisations.name)") , 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     ->get();

        return response()->json([
            "urbanisations" => UrbanisationCollection::make($urbanisations)
        ]);
    }

     /**
     * Display the specified resource.
     */
    public function show(string $hash)
    {
    
        $urbanisation = Urbanisation::where('hash', $hash)->first();
        //$city = (isNull($club->additional_information)) ? City::findOrFail($club->additional_information->city_id): null;
        $city = $urbanisation->city->name ?? '';
        $meets = Meeting::where('urbanisation_id', $urbanisation->id)->get();



        return response()->json([
            'urbanisation' => $urbanisation,
            'city' => $city,//($city!=null) ? $city->name.', '.$city->state->name: '',
            'meets' => $meets
        ]);
    }


    public function getMeeting($id){
         
        $meeting = Meeting::findOrFail($id);

        $questions = Question::where('meeting_id', $id)->get();

        //$meeting['properties'] = $meeting->properties;
        $meeting['urbanisation'] = $meeting->urbanisation;

        return response()->json( [
            'response' => 200,
            'meeting' => $meeting,
            'questions' => $questions
        ]);
    }

    
    public function getFinalReport($id){

        $meetingId = $id;
        $questions = Question::where('meeting_id', $meetingId)->get();

        $finalVotes = array();
       

        foreach ($questions as $question) {
            $totalVotes = Vote::where( 'question_id', $question->id )->count();
           
           $provisional = DB::select("SELECT COUNT(*) AS 'votes',answer_id, a.name, ROUND(SUM(o.total_coefficient),3) as total_coefficient 
                FROM votes v
                INNER JOIN answers a ON a.id = v.answer_id 
                INNER JOIN owners o ON v.owner_id = o.id
                WHERE v.question_id = ? GROUP BY answer_id ORDER BY a.id;", [$question->id]); 
            $resultVotes['question'] = $question->name;

            
            foreach ($provisional as $item ) {
                $woners = DB::select("SELECT o.name, o.total_coefficient, o.building, o.`floor`, o.letter, o.total_coefficient 
                    FROM owners o INNER JOIN votes v ON v.owner_id = o.id 
                    WHERE v.answer_id = ? ORDER BY o.building, o.id;", [$item->answer_id]);
                
                $item->owners = $woners;
                $item->percent = round($item->votes * 100 / $totalVotes, 2);
                
            }
            $resultVotes['result'] = $provisional;
            $finalVotes[] = $resultVotes;      

        }

        return response()->json([
            "message" => 200,
            "final_result" => $finalVotes
        ]);
        
    }


    public function getOpenHours( $range ){
        $openHours = collect([]);
        for ($i=$range->opening_time_id; $i <= $range->closing_time_id ; $i++) { 
            $openHours->push($i);
        }
        return $openHours;
    }
    
    public function config( Request $request ){
        
        $club = Club::where('hash', $request->hash)->first();
        $clubId = $club->id;

        $courtsBySport = Court::where('club_id', $clubId)->where('sport_type',$request->sport_selected )->get(['id', 'name']);
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
            ->select('reservations.id as reservation_id', 'reservation_hours.schedule_id as schedule_id', 'reservation_info.online as is_online',
                        'reservations.court_id as court_id', /*'reservation_info.name as reservation_name',*/
                        'courts.sport_type', 'reservations.start_time', 'reservations.end_time', 'reservations.type')
            ->orderBy('reservation_hours.schedule_id', 'ASC')
            ->get();

        

        $finalScheduleHours = collect([]);
        $finalScheduleHours = $scheduleHours;
        

        foreach ($finalScheduleHours as $item) {
            $item['courts'] = clone $courtsBySport;  
        }

        $majorSport = 0;
        $majorSportResult =  DB::table('courts')
            ->select(DB::raw('count(*) as total, sport_type'))
            ->whereNull('deleted_at')
            ->where('club_id', $clubId)
            ->groupBy('sport_type')
            ->first();
        if( $majorSportResult ){
            $majorSport = $majorSportResult->sport_type;
        }
      

        return response()->json([
            'message' => 200,
            'users_can_book' => $club->users_can_book,
            'club_mobile' => $club->mobile,
            'major_sport' => $majorSport,
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
                    "hour" => $hour_item->hour,
                ];
            })
        ]);    

    }

    public function createBooking( Request $request ){

        $validator = Validator::make($request->all(), [
            'court_id' => 'required|integer',
            'email' => 'required|email|max:50',
            'password' => 'max:50',
            'date' => 'required',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $club = Club::where('hash', $request->hash_club)->first();
        $clubId = $club->id;

        $credentials = request(['email', 'password']);
        $token =  auth('api')->attempt($credentials);
        
        if( false == $token ){
            return response()->json([
                'user' => request()->email,
                'password' => request()->password,
                'message' => "403 | NO MEMBER FOR BOOKING."
            ], 403);
        }
        $clubUser = ClubUser::where('club_id', $clubId)->where('user_id', auth("api")->user()->id)->first();
        if( $clubUser == null ){
            return response()->json([
                'user' => request()->email,
                'password' => request()->password,
                'message' => "403 | ACTIVE ON MATCHOPINT BUT NO MEMBER OF ".$club->name
            ], 403);
        }
        $user = auth("api")->user();

        $court = Court::findOrFail($request->court_id);

        if( $clubId != $court->club_id ){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }


        $hoursSelected = json_decode($request->hours_selected, 1);
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        
        $dateAux = Carbon::parse($date_clean)->format("Y-m-d");

        $correctHours = ReservationsController::correctlyHours($hoursSelected);
        if( $correctHours != ''){
            return response()->json([
                'message' => 422,
                'errors_text' => [$correctHours]
            ]);
        }

        if( true == ReservationsController::isFreeCourtSelectedHour($clubId, $request->court_id,$dateAux, $hoursSelected )){
            return response()->json([
                'message' => 422,
                'errors_text' => ['La hora seleccionada ya no está disponible']
            ]);
        }

        if( false == ReservationsController::isOpenClubSelectedHour($clubId, $request->day_week_number, $hoursSelected )){
            return response()->json([
                'message' => 422,
                'errors_text' => ['El club está cerrado a la hora que quiere reservar']
            ]);
        }

        $request->request->add(['club_id' => $clubId]);
        $request->request->add(["created_by_user_id" => $user->id]);
        $request->request->add(['user_id' => $user->id]);

        $reservation = Reservation::create($request->all());

        ReservationInfo::create([
            'reservation_id' => $reservation->id,
            'name' => $user->name,
            'surname' =>  $user->surname,
            'mobile' => $user->mobile,
            'online' => 1
        ]);

        
        foreach ($hoursSelected as $key => $hour) {
            $reservationHour = ReservationHour::create([
                'reservation_id' => $reservation->id,
                'start_time' => $hour['hour_start'],
                'end_time' => $hour['hour_end'],
                'schedule_id' => $hour['schedule_id']
            ]);
        }

        auth()->logout();

        return response()->json([
            'message' => 200,
            'reservation_id' => $reservation->id
        ]);
    }

    public function sendQuestionEmail(Request $request){
        $client = array();

        $client["name"] = $request->client_name;
        $client["email"] = $request->client_email;
        $client["club"] = $request->client_club;
        $client["mobile"] = $request->client_mobile;
        $client["comment"] = $request->client_comment;

        $sendEmail = Mail::to('support@weloveracket.com')->send( new SendEmail('question_from_home', 'Welcome', $client));
        if( $sendEmail){
            return response()->json([
                'message' => 200
            ]);
        }else{
            return response()->json([
                'message' => 422,
            ]);
        }
        
    }


    public function sendForgotPasswordEmail(Request $request){
        
        $user = User::where('email', $request->email)->first();
             
        if($user){
            $token = Str::random(30);
            $user['reset_link'] = env('HOME_URL').'new-password/'.$token;
            $user['token'] = $token;
            //$user->email
            $resertPasswordUser = PasswordResetToken::where('email', $user->email)->first();
            if( $resertPasswordUser ){
                $resertPasswordUser->delete();
            } 
           
            PasswordResetToken::create([
                'email' => $user->email,
                'token' => $token
            ]);

            $sendEmail = Mail::to($user->email)->send( new SendEmail('forgot-password', 'Recuerda contraseña', $user));
            if( $sendEmail){
                return response()->json([
                    'message' => 200
                ]);
            }else{
                return response()->json([
                    'message' => 422,
                ]);
            }
        }else{
            return response()->json([
                'message' => 420,
            ]);
        }

    }

    public function registerUserTournament( Request $request ){

        $validator = Validator::make($request->all(), [
            'category_selected_id' => 'required|integer',
            'email' => 'required|email|max:50',
            'password' => 'max:50'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $tournament = Tournament::where('hash', $request->hash_tournament)->first();
        $category = Category::findOrFail($request->category_selected_id);
        $club = Club::findOrFail($tournament->club_id);
        $clubId = $club->id;

        $credentials = request(['email', 'password']);
        $token =  auth('api')->attempt($credentials);
        

        if( false == $token ){
            return response()->json([
                'message' => 422,
                'errors_text' => ['No estás dado de alta en WeLoveRacket']
            ]);
        }

        $clubUser = ClubUser::where('club_id', $clubId)->where('status', 'ACCEPT')->where('user_id', auth("api")->user()->id)->first();
        if( $clubUser == null ){
            return response()->json([
                'message' => 422,
                'errors_text' => ['Activo en WeLoveRacket pero no eres socio del club. Manda solicudad de amistad']
            ]);
        }

        $user = auth("api")->user();
        
        $registerUsers = array();
        $registerUsersResult = DB::select("SELECT cp.user_id as id FROM couples c INNER JOIN couple_players cp ON c.id = cp.couple_id 
	                WHERE c.category_id = ? ;", [$request->category_selected_id]);
         
        foreach ($registerUsersResult as $key => $registerUser) {
            $registerUsers[] = $registerUser->id;
        }

        if( in_array($user->id, $registerUsers)){
            return response()->json([
                'message' => 422,
                'errors_text' => ['Ya estás registrado en esta categoría']
            ]);
        }
        
       

        if( $request->exists('couple_selected_id') == false){
            $couple = Couple::create([
                'club_id' => $clubId,
                'category_id' => $request->category_selected_id,
                'league_id' => $category->league_id,
                'tournament_id' => $category->tournament_id,
                'name' => 'automatic_name' 
            ]);
            $coupleId = $couple->id;
        }else{
            $coupleId = $request->couple_selected_id;
        }

        CouplePlayer::create([
            'user_id' => $user->id,
            'couple_id' => $coupleId,
            'substitute' => 0
        ]); 
        
        auth()->logout();
    
        return response()->json([
            'message' => 200,
            'tournament' => $tournament,
            'club' => $club
        ]);

    }

     /**
     * Display the specified resource.
     */
    public function getBooking(string $id)
    {
       
        $reservation = Reservation::findOrFail($id);
        $clubId = $reservation->club_id; 

        $bookingDate = Carbon::createFromFormat('Y-m-d H:i:s', $reservation['date'].' '.$reservation['start_time'].':00' );
        $nowDate = Carbon::now();
        $nowDate->addHours(12);
        
        $reservation['name'] = $reservation->info->name;
        $reservation['surname'] = $reservation->info->surname;
        $reservation['mobile'] = '****'.substr($reservation->info->mobile, 4, strlen($reservation->info->mobile));
        $reservation['can_cancel'] = $bookingDate->gt($nowDate);


        
        return response()->json( [
            'message' => 200,
            'reservation' => $reservation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    

    public function cancelBooking( Request $request )
    {

        $reservation = Reservation::findOrFail($request->reservation_id);
        
        $ownerEmail = $reservation->user->email;
        $request->request->add(['email' => $ownerEmail]);

        $credentials = request(['email', 'password']);
        $token =  auth('api')->attempt($credentials);
        
        if( false == $token ){
            return response()->json([
                'user' => request()->email,
                'message' => "403 | BOOKING PASSWORD INCORRECT."
            ], 403);
        }

        $reservation->info->delete();
        $reservationsHours = $reservation->hours;
        foreach ($reservationsHours as $hour) {
            $hour->delete();
        }
        $reservation->delete();
        auth()->logout();


        return response()->json([
            'message' => 200
        ]);
    }


    
     /**
     * Display the specified resource.
     */
    public function getLeagues( Request $request )
    {
        $club = Club::where('hash', $request->hash_club)->first();
        $clubId = $club->id;
       
        $search = $request->search;
        
        $leagues = League::where('club_id', $clubId)
                     ->where("name" , 'like', '%'.$search.'%')
                     ->orderBy('start_date', 'desc')
                     ->paginate(10);

        return response()->json([
            "total" => $leagues->total(),
            "leagues" => $leagues->map(function($item){
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "sport_type" => $item->sport_type,
                    "start_date" => $item->start_date,
                    "created_at" => $item->created_at->format("Y-m-d h:i:s"),
                    "preregistrations" => LeaguesController::preregistrations($item->id),
                    "match_finished" => LeaguesController::leagueMatchFinished($item->id),
                    "match_pending" => LeaguesController::leagueMatchPending($item->id)
                ];
            })
        ]);
    }

    

    public static function majorSport( $clubId ){

        $sports =  DB::table('courts')
            ->select(DB::raw('count(*) as total, status'))
            ->whereNull('deleted_at')
            ->where('club_id', 1)
            ->groupBy('sport_type')
            ->get();

        return response()->json([
            'message' => 200,
            'sports' => $sports
        ]);

    }


    public function getLeague(string $id)
    {
       
        $league = League::findOrFail($id);


        $matchsFiniched = LeaguesController::leagueMatchFinished($id);

        //$league['avatar'] = env("APP_URL")."storage/".$league->avatar;
        $league['avatar'] = $league->avatar ? env("APP_URL")."storage/".$league->avatar : 'assets/img/img-06.jpg';
        $league['categories'] = $league->categories;
        $league['type_match'] = ($league->match_type == 'double') ? 1 : 2;
        
        return response()->json( [
            'message' => 200,
            'match_finiched' => $matchsFiniched,
            'league' => $league,
            'club_name' => $league->club->name,
            'club_hash' => $league->club->hash, 
            'club_email' => $league->club->email
        ]);
    }


    public function getTournaments( Request $request )
    {
        $club = Club::where('hash', $request->hash_club)->first();
        $clubId = $club->id;
       
       $tournaments = Tournament::where('club_id', $clubId)
                     ->orderBy('start_date', 'asc')
                     ->where('is_draft', 0)
                     ->paginate(10);

        return response()->json([
            "total" => $tournaments->total(),
            "tournaments" => $tournaments->map(function($item){
                return [
                    "id" => $item->id,
                    "hash" => $item->hash,
                    "sport_type" => $item->sport_type,
                    "name" => $item->name,
                    "start_date" => $item->start_date,
                    "end_date" => $item->end_date,
                    "price" => $item->price,
                    "price_member" => $item->price_member,
                    "time_per_match" => $item->time_per_match,
                    "draw_generated" => $item->draw_generated,
                    "created_at" => $item->created_at->format("Y-m-d h:i:s"),
                    "tournament_open" => TournamentsController::preregistrations($item->id),
                    "matchs_finished" => TournamentsController::tournamentMatchFinished($item->id),
                    "matchs_pending" => TournamentsController::tournamentMatchPending($item->id),
                    "is_finished" => $item->isFinisched()
                ];
            })
        ]);
    }
    
    public function getTournament(string $hash)
    {
       
        $tournament = Tournament::where('hash', $hash)->first();

        
        //$tournament['avatar'] = env("APP_URL")."storage/".$tournament->avatar;
        $tournament['avatar'] = $tournament->avatar ? env("APP_URL")."storage/".$tournament->avatar : 'assets/img/img-06.jpg';
        $tournament['categories'] = $tournament->categories;
         
        return response()->json( [
            'message' => 200,
            'tournament' => $tournament,
            'tournament_open_registration' => TournamentsController::preregistrations($tournament->id),
            'club_name' => $tournament->club->name,
            'club_hash' => $tournament->club->hash,
            'club_email' => $tournament->club->email
        ]);
    }


    public function getDraw(Request $request){
        return TournamentsController::getDraw($request->id, $request->type);
    }

    public function getDataCategoryLeague( $category_id ){
        
        $couples = Couple::where('category_id', $category_id)->get();
    
        $jorneys = Journey::where('category_id', '=', $category_id)
            ->orderBy('id', 'asc')->get();
        
        $rankingList = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->leftJoin("couple_results","couples.id", "=", "couple_results.couple_id")
            ->where('couple_results.deleted_at', NULL)
            ->where('couples.category_id', $category_id)
            ->select('couple_results.*', 'couples.name', DB::raw("couples.id as couple_id"))
            ->orderBy('couple_results.total_points', 'desc')
            ->orderBy('couple_results.sets_won', 'desc')
            ->orderBy('couple_results.games_avg', 'desc')
            //->orderBy('couple_results.sets_lost', 'asc')
            ->orderBy('couples.name', 'asc')
            ->get();  

        $ranking = $rankingList->map(function($item) {
            $couple = Couple::findOrFail($item->couple_id);
            return [ 
                'id' => $item->id,
                'couple_id' => $item->couple_id,
                'total_points' => $item->total_points,
                'matches_played' => $item->matches_played,
                'matchs_won' => $item->matchs_won, 
                'matchs_lost' => $item->matchs_lost, 
                'games_won' => $item->games_won, 
                'games_lost' => $item->games_lost, 
                'sets_won' => $item->sets_won, 
                'sets_lost' => $item->sets_lost, 
                'avg_games' => $item->games_avg,
                'name' => $item->name,
                'players' => $couple->players->map(function($player){
                    return [
                        "name" => $player->user->name,
                        "surname" => $player->user->surname
                    ];  
                }),
            ];
        });

        return response()->json( [
            'message' => 200,
            'couples' => CoupleCollection::make($couples),
            'journeys' => $jorneys->map(function($item){
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "description" => $item->description,
                    "matchs_pending" => JourneyController::getPendingResult($item->id),
                    "date" => Carbon::parse($item->date)->format("d M Y"),
                    "craeted_at" => $item->created_at->format("Y-m-d H:m"),
                ];
            }),
            'ranking' => $ranking
        ]);

    }


    public static function getClasificationCategory( $category_id ){



        $clasification = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->leftJoin("tournament_couples_clasifications","couples.id", "=", "tournament_couples_clasifications.couple_id")
            ->where('tournament_couples_clasifications.deleted_at', NULL)
            ->where('couples.category_id', $category_id)
            ->select('tournament_couples_clasifications.*', 'couples.name', 'couples.league_number', DB::raw("couples.id as couple_id"))
            ->orderBy('tournament_couples_clasifications.total_points', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_won', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_lost', 'asc')
            ->orderBy('tournament_couples_clasifications.games_avg', 'desc')
            ->orderBy('couples.name', 'asc')
            ->get();  

    $ranking =  $clasification->map(function($item) {
        
        $couple = Couple::findOrFail($item->couple_id);
        $couplePlayersName = '';
        if( $couple != false ){
            $couple->players->map(function($player) use(&$couplePlayersName){
                if(strlen($player->user->name) > 14){
                    $couplePlayersName .= substr($player->user->name, 0, 15).'.'.' - ';
                }else{
                    $couplePlayersName .= $player->user->name.' - '; 
                }
                });
        }
        if(strlen($couplePlayersName) > 0){
            $couplePlayersName = substr($couplePlayersName, 0, -3);
        }
        return [
            'id' => $item->couple_id,
            'league_number' => $item->league_number,
            'total_points' => $item->total_points, 
            'matches_played' => $item->matches_played,
            'matchs_won' => $item->matchs_won,
            'matchs_lost' => $item->matchs_lost,
            'games_won' => $item->games_won,
            'games_lost' => $item->games_lost,
            'games_avg' => $item->games_avg,
            'sets_won' => $item->sets_won,
            'sets_lost' => $item->sets_lost,
            'couple_id' => $item->couple_id,
            'players' => $couple->players->map(function($player){
                    return [
                        "title" => $player->user->name.' '.$player->user->surname
                    ];  
                }),
        ];
    });

    return $ranking;





        return $ranking;
    }

    public function getMatchesSimpleLeague($category_id){

        $category = Category::findOrFail($category_id);

        $matchesCategory = DB::table("tournament_matches")
                    ->where('tournament_matches.deleted_at', NULL)
                    ->where('tournament_matches.category_id', $category_id);
        if( $category->type == 2){
            $matchesCategory->whereNotNull('tournament_matches.league_number');
        }
        $matchesCategory = $matchesCategory->leftJoin('tournament_matches_date_court', 'tournament_matches.id', '=', 'tournament_matches_date_court.tournament_match_id')
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->leftJoin('courts','courts.id','tournament_matches_date_court.court_id' )
            ->select('tournament_matches.id',
                'tournament_matches.round',
                'tournament_matches.local_couple_id',
                'tournament_matches.visiting_couple_id',
                'tournament_matches.result_set_1',
                'tournament_matches.result_set_2',
                'tournament_matches.result_set_3',
                'tournament_matches_date_court.date',
                'tournament_matches.league_number',
                'tournament_matches_date_court.match_finished',
                'tournament_matches.is_second_leg',
                'courts.name')
            ->where('tournament_matches.main_draw', 1)
            ->orderBy('tournament_matches.round', 'asc')
            ->orderBy('tournament_matches.league_number', 'asc')
            ->get();

        $maxRound = TournamentMatch::where('category_id', '=', $category_id)->max('round');
         
        $jorneys =  $matchesCategory->map(function($item) {
            list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = TournamentsController::getResultMatch($item->id);
            $setResult1 = explode('-',$item->result_set_1);
            $setResult2 = explode('-',$item->result_set_2);
            $setResult3 = explode('-',$item->result_set_3);
            $localCouple = Couple::findOrFail($item->local_couple_id);
            $visitingCouple = Couple::findOrFail($item->visiting_couple_id);
            return [
                'id' => $item->id,
                'journey' => $item->round,
                'local_couple_id' => $localCouple->id,
                'local_players' => $localCouple->players->map(function($player) {
                    return [
                        "title" => $player->user->name
                    ];  
                }),
                'visiting_couple_id' => $visitingCouple->id,
                'visiting_players' => $visitingCouple->players->map(function($player) use($item){
                    return [
                        "title" => $player->user->name
                    ];  
                }),
                'result_set_1_local' => $item->result_set_1 ? $setResult1[0] : '-',
                'result_set_2_local' => $item->result_set_2 ? $setResult2[0] : '-',
                'result_set_3_local' => $item->result_set_3 ? $setResult3[0] : '-',
                'result_set_1_visiting' => $item->result_set_1 ? $setResult1[1] : '-',
                'result_set_2_visiting' => $item->result_set_2 ? $setResult2[1] : '-',
                'result_set_3_visiting' => $item->result_set_3 ? $setResult3[1] : '-',
                'time' =>  $item->date ? Carbon::parse($item->date)->format('N H:i\h jS \of F') : '',
                'score_local' => $scoresLocal,
                'local_winner' => $localWinner,
                'visiting_winner' => $visitingWinner,
                'score_visiting' => $scoresVisiting,
                'league_number' => $item->league_number,
                'is_second_leg' => $item->is_second_leg,
                'court_name' => $item->name
            ];
        });

        return [ $jorneys , $maxRound ];
       
    }

    public function getDataCategoryTournament( $category_id ){
        
        $category = Category::findOrFail($category_id);
        $couples = Couple::where('category_id', $category_id)->get();

       
        $ranking = array();
        if( $category->type == 1 || $category->type == 2 || $category->type == 6){  // Only round lobyn league
            $ranking = self::getClasificationCategory($category_id);
        }    
        $matches = array();
        $totalJourneys = 0;
        if( $category->type == 1 || $category->type == 2 || $category->type == 6){  // Only round lobyn league
            list($matches, $totalJourneys) =  self::getMatchesSimpleLeague($category_id);
        } 
    
        return response()->json( [
            'message' => 200,
            'couples' => CoupleCollection::make($couples),
            'category' => $category,
            'ranking' => $ranking,
            'matches' => $matches,
            'total_journeys' => $totalJourneys
        ]);

    }
    
    

    public function getCoupleResults(string $couple_id){
        
        $results = CoupleResult::where('couple_id', $couple_id)->first();
        
        $matches = JourneyMatch::where('local_couple_id', '=', $couple_id)
            ->orWhere('visiting_couple_id', '=', $couple_id)->orderBy('id', 'asc')->get();
          
        return response()->json( [
            'message' => 200,
            'results' => $results,
            'type_league' => $results ? $results->couple->league->match_type : 'doubles',
            "matches" => $matches->map(function($item){
                $localCouple = Couple::findOrFail($item->local_couple->id);
                $visitingCouple = Couple::findOrFail($item->visiting_couple->id);
                return [
                    "id" => $item->id,
                    'journey' => $item->journey->name,
                    'local_couple' => $item->local_couple->name,
                    'local_players' => $localCouple->players->map(function($player){
                        return [
                            "title" => $player->user->name.' '.$player->user->surname
                        ];  
                    }),
                    'visiting_players' => $visitingCouple->players->map(function($player){
                        return [
                            "title" => $player->user->name.' '.$player->user->surname
                        ];  
                    }),
                    'visiting_couple' => $item->visiting_couple->name,
                    'result_set_1' => $item->result_set_1,
                    'result_set_2' => $item->result_set_2,
                    'result_set_3' => $item->result_set_3,
                    "created_at" => $item->updated_at->format("Y-m-d h:i:s"),
                    'match_finisehd' => $item->match_finished
                ];
            })
        ]);
    }
    

    public function getCouple(string $couple_id){
        
        $couple = Couple::where('id', $couple_id)->get();

        return response()->json( [
            'message' => 200,
            'couple' => CoupleCollection::make($couple)
        ]);
    }

    public function getMatchsJourney(string $journey_id){
        
        $journey = Journey::findOrFail($journey_id);

        return response()->json([
            'message' => 200,
            'journey_name' => $journey->name,
            'type_matchs' => $journey->league->match_type,
            'description' => $journey->description,
            'date' => $journey->date,
            'matchs' => $journey->matchs->map(function($item){
                return [
                    "id" => $item->id,
                    //"local_team" => $item->local_couple->name,
                    "local_players" => $item->local_couple ? $item->local_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name,
                            "surname" => $player->user->surname
                        ];
                    }) : [],
                    //"visiting_team" => $item->visiting_couple->name,
                    "visiting_players" => $item->visiting_couple ? $item->visiting_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name,
                            "surname" => $player->user->surname
                        ];
                    }): [], 
                    "result_set_1" => $item->result_set_1,
                    "result_set_2" => $item->result_set_2,
                    "result_set_3" => $item->result_set_3,
                    "match_finisehd" => $item->match_finished
                ];
            })
        ]);
    }

    protected function getUserByToken( $token ){
        $passwordTokenUser = PasswordResetToken::where('token', $token)->first();
        
        return response()->json( [
            'message' => 200,
            'token' => $token,
            'email' => optional($passwordTokenUser)->email ?? '',
            'isTokenActive' =>  optional($passwordTokenUser)->isActiveToken() ?? false
        ]);
     
    }

    protected function getUserToVerifyEmail( $token )
    {
        date_default_timezone_set('Europe/Madrid');
        $emailToVerify = ConfirmEmail::where('token', $token)->orderBy('id', 'desc')->first();

        if(optional($emailToVerify)->isActiveToken() ){
            $user = User::findOrFail($emailToVerify->user_id); 
            $user->update([
                'email_verified_at' => Carbon::now()
            ]);
            if( $user->club_id != 0){
                $club = Club::findOrFail($emailToVerify->club_id); 
                $club->update([
                    'club_verified_at' => Carbon::now()
                ]);
            }
         //   $emailToVerify->delete();
        }


        $client['club_name'] = $user->name;
        
        $sendEmail = Mail::to($user->email)->send( new SendEmail('welcome_message', '¡Hola!', $client)); 
        return response()->json( [
            'message' => 200,
            'token' => $token,
            'club_name' => $user->name,
            'email' => optional($emailToVerify)->email ?? '',
            'isTokenActive' =>  optional($emailToVerify)->isActiveToken() ?? false
        ]);
     
    }


    public static function sendEmailVerifyClub( $email )
    {
        $user = User::where('email', $email)->first();
         
        $token = Str::random(30);
        if( $user->club_id != 0 ){
            $club = Club::findOrFail($user->club_id);
        }

        $confirmEmail = ConfirmEmail::create([
            'club_id' => $user->club_id,
            'email' => $user->email,
            'user_id' => $user->id,
            'token' => $token
        ]);

        $client['verify_link'] = env('HOME_URL').'verify-email/'.$token;
        if( $user->club_id != 0 ){
            $client['club_name'] = $club->name;
        }else{
            $client['club_name'] = '';
        }

        $sendEmail = Mail::to($user->email)->send( new SendEmail('confirm_email_message', 'Verifica tu cuenta', $client));
        return response()->json( [
            'message' => 200
        ]);
     
    }

    protected function updatePasswordUser( Request $request ){
        $passwordTokenUser = PasswordResetToken::where('token', $request->token)->first();
        
        if( optional($passwordTokenUser)->isActiveToken() ?? false){
            $user = User::where('email', $passwordTokenUser->email )->first();
            $user->update([
                'password' => bcrypt($request->password)
            ]);
            $passwordTokenUser->delete();
            return response()->json( [
                'message' => 200,
            ]);
        }
        return response()->json( [
            'message' => 420,
            //'token' => $token,
            //'email' => $passwordTokenUser->email,
            'isTokenActive' =>  optional($passwordTokenUser)->isActiveToken() ?? false
        ]);
     
    }

    
    

}
