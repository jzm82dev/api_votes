<?php

namespace App\Http\Controllers\Tournament;

use stdClass;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Mail\SendEmail;
use App\Models\Club\Club;
use App\Models\Contestants;
use App\Models\Court\Court;
use App\Models\Round\Round;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Couple\Couple;
use Ramsey\Uuid\Type\Integer;
use App\Models\Category\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CouplePlayer;
use App\Http\Controllers\Controller;
use App\Models\Club\ClubScheduleDay;
use Illuminate\Support\Facades\Mail;
use App\Models\Tournament\Tournament;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Club\ClubResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Tournament\TournamentMatch;
use App\Http\Controllers\Journey\JourneyController;
use App\Models\Tournament\TournamentMatchDateCourt;
use App\Models\Tournament\TournamentScheduleDayHour;
use App\Http\Controllers\Category\CategoryController;
use App\Models\Club\ConfirmEmail;
use App\Models\Tournament\TournamentCouplesClasification;

class TournamentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $this->authorize('viewAny', Tournament::class);

        $search = $request->search;
        $clubId = auth("api")->user()->club_id;
        
        $tournaments = Tournament::where('club_id', $clubId)
                     ->where("name" , 'like', '%'.$search.'%')
                     ->orderBy('start_date', 'asc')
                     ->paginate(20);

        return response()->json([
            "total" => $tournaments->total(),
            "tournaments" => $tournaments->map(function($item){
                return [
                    "id" => $item->id,
                    "sport_type" => $item->sport_type,
                    "name" => $item->name,
                    "start_date" => $item->start_date,
                    "end_date" => $item->end_date,
                    "price" => $item->price,
                    "price_member" => $item->price_member,
                    "time_per_match" => $item->time_per_match,
                    "tournament_open" => self::preregistrations($item->id),
                    "draw_generated" => $item->draw_generated,
                    "matchs_finished" => self::tournamentMatchFinished($item->id),
                    "matchs_pending" => self::tournamentMatchPending($item->id),
                    "is_draft" => $item->is_draft,
                    "created_at" => $item->created_at->format("Y-m-d h:i:s"),
                    "is_finished" => $item->isFinisched()
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Tournament::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'sport_type' => 'required|integer',
            'start_date' => 'required',
            'end_date' => 'required',
            'price' => 'required|decimal:0,2',
            'price_member' => 'required|decimal:0,2',
            'time_per_match' => 'required|integer',
            'date_starts_registration' => 'required',
            'hour_starts_registration' => 'required',
            'date_ends_registration' => 'required',
            'hour_ends_registration' => 'required'
        ]);


        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

         $clubId = auth("api")->user()->club_id;

         $validTournament = Tournament::where('name', $request->name)
                         ->where('club_id', $clubId)
                         ->first();
 
         if($validTournament){
             return response()->json([
                 'message' => 403,
                 'message_text' => 'Ya existe una torneo con este nombre'
             ]);
         }
 
         $this->authorize('create', Tournament::class);
     
         if($request->hasFile('imagen')){
            $path = Storage::putFile("tournaments", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }
 
         $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->start_date);
         $request->request->add(["start_date" => Carbon::parse($date_clean)->format("Y-m-d")]);

         $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->end_date);
         $request->request->add(["end_date" => Carbon::parse($date_clean)->format("Y-m-d")]);

         $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_starts_registration);
         $request->request->add(["date_starts_registration" => Carbon::parse($date_clean)->format("Y-m-d")]);

         $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_ends_registration);
         $request->request->add(["date_ends_registration" => Carbon::parse($date_clean)->format("Y-m-d")]);
         
 
         $request->request->add(['club_id' => $clubId]);

         $hash = str()->random(6);
         $request->request->add(['hash' => $hash]);
 
         $tournamentInserted = Tournament::create($request->all());

         return response()->json([
            'message' => 200,
            'id_tournament' => $tournamentInserted->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
        $tournament = Tournament::findOrFail($id);
        $user = auth('api')->user();

        $this->authorize('view', $tournament);
        
        $tournament['avatar'] = env("APP_URL")."storage/".$tournament->avatar;
        $tournament['category'] = $tournament->categories->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "match_type" => $item->match_type,
                "total_couple" => Couple::where('category_id', $item->id)->count(),
                "type" => is_null($item->type) ? 0 : $item->type     
            ];
        });
        $tournament['schedule'] = $tournament->schedule;
        $tournament['categories'] = null;
       
        
        return response()->json( [
            'tournament->club_id' => $tournament->club_id,
            'user->club_id' => $user->club_id,
            'message' => 200,
            'league' => $tournament
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $tournament = Tournament::findOrFail($id);
        $this->authorize('update', $tournament);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'sport_type' => 'required|integer',
            'start_date' => 'required',
            'end_date' => 'required',
            'price' => 'required|decimal:0,2',
            'price_member' => 'required|decimal:0,2',
            'time_per_match' => 'required|integer',
            'date_starts_registration' => 'required',
            'hour_starts_registration' => 'required',
            'date_ends_registration' => 'required',
            'hour_ends_registration' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       $invalidTournament = Tournament::where('name', $request->name)
                           ->where('id', '<>', $id) 
                           ->first();

        if($invalidTournament){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un torneo con este nombre'
            ]);
        }
        
        $trace = '0';
        $this->authorize('update', $tournament);
        
        if($request->hasFile('imagen')){
            $trace.='1';
            if( $tournament->avatar){
               $result = Storage::delete($tournament->avatar);
               $trace.='2';
            }
            $trace.='3';
            $path = Storage::putFile("tournaments", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->start_date);
        $request->request->add(["start_date" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->end_date);
        $request->request->add(["end_date" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_starts_registration);
         $request->request->add(["date_starts_registration" => Carbon::parse($date_clean)->format("Y-m-d")]);

         $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date_ends_registration);
         $request->request->add(["date_ends_registration" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $tournament->update($request->all());

        return response()->json([
            'message' => 200,
            'tournament' => $tournament,
            'trace' => $trace
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tournament = Tournament::findOrFail($id);

        $this->authorize('delete', $tournament);
        
        $categories = Category::where('tournament_id', $id)->get();
        foreach ($categories as $key => $category) {
            $couples = Couple::where('category_id', $category->id)->get();
            foreach ($couples as $key => $couple) {
                CouplePlayer::where('couple_id', $couple->id)->delete();
                $couple->delete();
            }
            $category->delete();
        }


        if( $tournament->avatar){
            Storage::delete($tournament->avatar);
        }

        $tournament->delete();

        return response()->json([
            'message' => 200
        ]);
    }


    public static function tournamentMatchFinished($tournamentId){
        
        $matchFinished = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.tournament_id', $tournamentId)
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.match_finished', '=', 1)
            ->count();

        return $matchFinished;
    }

    public static function tournamentMatchPending($tournamentId){
        
        $matchFinished = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.tournament_id', $tournamentId)
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.match_finished', '=', 0)
            ->count();

        return $matchFinished;
    }

    public static function preregistrations($tournamentId){
        $tournament = Tournament::findOrFail($tournamentId);
        if($tournament->draw_generated == 0){
            return true;
        }

        return false;
    }
    

    public function defineAvailableCourtsHours( $tournamentId ){

        $tournament = Tournament::findOrFail($tournamentId);

        if( $tournament->schedule->count() > 0){ // has diferent schedule for the tournament
            foreach ($tournament->schedule as $key => $schedule) {
                self::createTournametMatchCourt($tournamentId, $schedule->date, $schedule->opening_time, $schedule->closing_time);
            }
        }else{ // take the normal daily schedule of the club 
            $arrayStartDate = explode('-',$tournament->start_date);
            $arrayEndDate = explode('-',$tournament->end_date);

            $startDateTournament = Carbon::create($arrayStartDate[0], $arrayStartDate[1], $arrayStartDate[2], 0, 0, 0);
            $endDateTournament = Carbon::create($arrayEndDate[0], $arrayEndDate[1], $arrayEndDate[2], 23, 59, 0);
            
            $scheduleHours = collect([]);
            
            while($startDateTournament <= $endDateTournament) {
                $dayOfWeek = 'day_'.$startDateTournament->dayOfWeek;
                if( $startDateTournament->dayOfWeek == 0){
                    $dayOfWeek = 'day_7';
                } 
                $schedulesDay = ClubScheduleDay::where('club_id', $tournament->club_id)->where('day_id', $dayOfWeek)->get();
                foreach ($schedulesDay as $scheduleDay) {
                    foreach ($scheduleDay->schedulesHours as $hours) {
                       self::createTournametMatchCourt($tournamentId, $startDateTournament->format("Y-m-d"), $hours->opening_time, $hours->closing_time);
                    }
                }
                $startDateTournament->addDay();
            }
        }

        $resp['message'] = 200;
        return response()->json(
            $resp    
        );
    }


    public function createTournametMatchCourt($tournamentId, $day, $startHour, $endHour){
        $tournament = Tournament::findOrFail($tournamentId);

        $courts = Court::where('club_id', $tournament->club_id )->where('sport_type', $tournament->sport_type)->get();
        $arrayDate = explode('-', $day);
        $arrayHourStart = explode(':', $startHour);
        $arrayHourEnd = explode(':', $endHour);
        $dateStart = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourStart[0], $arrayHourStart[1], 0);
        $dateEnd = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourEnd[0], $arrayHourEnd[1], 0);
        
        $matchDate = $dateStart;
        while($dateStart < $dateEnd){
            foreach ($courts as $court) {
                TournamentMatchDateCourt::create([
                    'tournament_id' => $tournamentId,
                    'court_id' => $court->id,
                    'date' => $matchDate,
                    'match_finished' => 0
                ]); 
            }
            $matchDate = $dateStart->addMinutes($tournament->time_per_match);
        }           
    }


    /*
    public function assignMatchSchedule( $tournamentId ){

        $tournament = Tournament::findOrFail($tournamentId);
        $this->authorize('configure', $tournament);
        
        $resp = collect([]);
        $totalGapsTournaments = TournamentMatchDateCourt::where("tournament_id", $tournamentId)->count();
        $totalMatchsTournament = TournamentMatch::where("tournament_id", $tournamentId)->count();
        if( $totalGapsTournaments < $totalMatchsTournament){
            $resp['message'] = 200;
            $resp['total_matchs'] = $totalMatchsTournament;
            $resp['total_courts_schedule'] = $totalGapsTournaments ;
            $resp['error'] = "No hay suficiente pistas asignadas";
            return response()->json(
                $resp    
            );
        }

        if(true == self::scheduleFinalsMatch($tournamentId)){
            $resp['schedule_finals'] = true;
        }
        if(true == self::assignMatchCoupleWithSchedule( $tournamentId)){
            $resp['schedule_couples_limited_hours'] = true;
        }

        $elements = DB::table('tournament_matches_date_court')
            ->select('tournament_match_id  as id')
            ->where('tournament_id', $tournamentId)
            ->whereNotNull('tournament_match_id')
            ->get();
        $ids = array();
        foreach ($elements as $key => $value) {
            $ids[] = $value->id;
        }

        $matchesTournament = TournamentMatch::where('tournament_id', $tournamentId)
                ->whereNotIn('id', $ids)
                ->orderBy('round', 'asc')->get();
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $tournamentId)
                ->whereNull('tournament_match_id')->get();

        $cont = 0;
        foreach ($matchesTournament as $match) { 
            if($cont > count($viablesSchedules)){
                break;
            }
            $viablesSchedules[$cont]->update(['tournament_match_id' => $match->id]);
            $cont ++;
        }
        $resp['message'] = 200;
        return response()->json(
            $resp    
        );
        
    }

    */

    /*
    public function assignBackDrawMatchScheduleTournament(string $categoryId){
       
        // Assign the final match the last day and day viable
        $matchFinal = TournamentMatch::where('category_id', $categoryId)->where('back_draw', 1)->orderBy('round', 'desc')->first();
        if($matchFinal){
            $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $matchFinal->tournament_id)
                ->whereNull('tournament_match_id')
                ->orderBy("date", "desc")
                ->first();
          //  $viablesSchedules->update( ['tournament_match_id' => $matchFinal->id] );
        }

        // Asignamos los partidos de las rondas siguientes (no de la final)

        
        $maxRound = TournamentMatch::where('category_id', '=', $categoryId)->where('back_draw', 1)->max('round');
        $timeBetweenMarchs = self::getMatchtimeIntervalBetweenMatchs($categoryId);
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $matchFinal->tournament_id)->whereNull('tournament_match_id')->orderBy('date')->get();
        $otherMatchs = TournamentMatch::where('category_id', $categoryId)->where('back_draw', 1)->where("round", '<', $maxRound)->orderBy('id')->get();
        $cont = 0;
        foreach ($otherMatchs as $match) { 
            if($cont > count($viablesSchedules)){
                break;
            }
            $gofor = '';
            // get date of previous round
            $order = intdiv($match->order, 2);
            $round = $match->round - 1;
            $previusMatch = DB::table('tournament_matches_date_court')
                ->where('tournament_matches_date_court.deleted_at', NULL)
                ->join('tournament_matches', 'tournament_matches_date_court.tournament_match_id', '=', 'tournament_matches.id')
                ->where('tournament_matches.deleted_at', NULL)
                ->where('tournament_matches.order', $order)
                ->where('tournament_matches.round', $round)
                ->where('tournament_matches.back_draw', 1)
                ->where('tournament_matches.category_id', $categoryId)
                ->select('date')
                ->first();
                if( false == $previusMatch){
                    $previusMatch = DB::table('tournament_matches_date_court')
                        ->where('tournament_matches_date_court.deleted_at', NULL)
                        ->join('tournament_matches', 'tournament_matches_date_court.tournament_match_id', '=', 'tournament_matches.id')
                        ->where('tournament_matches.deleted_at', NULL)
                        ->whereIn('tournament_matches.round', [0, 1])
                        ->where('tournament_matches.main_draw', 1)
                        ->where('tournament_matches.category_id', $categoryId)
                        ->select('date')->orderBy('date', 'desc')
                        ->first();
                    
                }

                $arrayDate = explode(' ',$previusMatch->date);
                $arrayDay = explode('-',$arrayDate[0]);
                $arrayHour = explode(':',$arrayDate[1]);
                $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
                $endMatch->addMinutes($timeBetweenMarchs);

                $filtered_collection = $viablesSchedules->filter(function ($item) use($endMatch) {
                    return $item->date > $endMatch && $item->tournament_match_id == null;
                })->first();
                $filtered_collection->update(['tournament_match_id' => $match->id]);
                
            $cont ++;

        }

        $resp['message'] = 200;
        $resp['gofor'] = $gofor;
       // $resp['matchs'] = $otherMatchs;
        $resp['previusMatch'] = $previusMatch;
        return response()->json(
            $resp    
        );
        
    }

    */

    public function getLastMatchMainDraw(string $categoryId){
        $timeBetweenMarchs = self::getMatchtimeIntervalBetweenMatchs($categoryId);

        $previusMatch = DB::table('tournament_matches_date_court')
                ->where('tournament_matches_date_court.deleted_at', NULL)
                ->join('tournament_matches', 'tournament_matches_date_court.tournament_match_id', '=', 'tournament_matches.id')
                ->where('tournament_matches.deleted_at', NULL)
                ->whereIn('tournament_matches.round', [0, 1])
                ->where('tournament_matches.main_draw', 1)
                ->where('tournament_matches.category_id', $categoryId)
                ->select('date')->orderBy('date', 'desc')
                ->first();
        $arrayDate = explode(' ',$previusMatch->date);
        $arrayDay = explode('-',$arrayDate[0]);
        $arrayHour = explode(':',$arrayDate[1]);
        $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
        $endMatch->addMinutes($timeBetweenMarchs);

        return $endMatch;
    }

    public function scheduleFinalsMatchByCategory( $categoryId, string $typeDraw ){

        $category = Category::findOrFail($categoryId);
        $match = TournamentMatch::where('category_id', $categoryId);
        if( $typeDraw == 1){
            $match->where('main_draw', 1);
        }else{
            $match->where('back_draw', 1);
        }
        $match =  $match->orderBy('round', 'desc')->first();
        
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
                ->whereNull('tournament_match_id')
                ->orderBy("date", "desc")
                ->first();
        
        if($match){
            $viablesSchedules->update(['tournament_match_id' => $match->id]);
        }
        
        
        return true;

    }

    public function assignFirstsMatchesScheduleBackDrawByCategory(string $categoryId){
        $category = Category::findOrFail($categoryId);
        
        $matches = TournamentMatch::where('category_id', $categoryId);
       
        $matches->where('back_draw', 1);
        $totalCouples = count($category->couples->where('to_back_draw', 1));
        $lastMatchDate = self::getLastMatchMainDraw($categoryId);


        $lastMatchFirstRound = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.category_id', $categoryId)
            ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->where('tournament_matches.round', '=', 1)
            ->where('tournament_matches.main_draw', '=', 1)
            ->max('tournament_matches_date_court.date');

            $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
            ->whereNull('tournament_match_id')
            ->whereDate('date', '>=', $lastMatchFirstRound)
            ->orderBy('date')->get();
        

        $matches = $matches->orderBy('round', 'desc')->count();
        
        $elements = DB::table('tournament_matches_date_court')
                ->select('tournament_match_id  as id')
                ->where('tournament_id', $category->tournament_id)
                ->whereNotNull('tournament_match_id')
                ->get();
        $scheduledMatches = array();
        foreach ($elements as $key => $value) {
            $scheduledMatches[] = $value->id;
        }
        
        list($rounds, $headCouples) = self::getTotalRounds($totalCouples);
        if( $headCouples == 0){
            $matchsFirstRound = TournamentMatch::where("category_id", $categoryId)->whereNotIn('id', $scheduledMatches)->where("round", 0);
            $matchsFirstRound->where("back_draw", 1);
            $matchsFirstRound = $matchsFirstRound->orderBy('id')->get();
        }else{
            $secoundRound = TournamentMatch::where("category_id", $categoryId)->whereNotIn('id', $scheduledMatches)->where("round", 1);
            $firstRound = TournamentMatch::where("category_id", $categoryId)->whereNotIn('id', $scheduledMatches)->where("round", 0)
                ->whereNotNull('local_couple_id')->whereNotNull('visiting_couple_id');
            
            $secoundRound->where("back_draw", 1);
            $firstRound->where("back_draw", 1);
            
            $secoundRound = $secoundRound->orderBy('id', 'desc')->get();
            $firstRound = $firstRound->orderBy('id', 'desc')->get();
            $matchsFirstRound = $firstRound;//->merge($secoundRound);                
        }

        $cont = 0;
        foreach ($matchsFirstRound as $match) { 
            if($cont > count($viablesSchedules)){
                break;
            }
            $viablesSchedules[$cont]->update(['tournament_match_id' => $match->id]);
            $cont ++;
            $ids[] = $match->id;
        }
        return true;

    }


    public function getLastMatchsPreviousRound( $matchId, $typeDraw ){
        
        $lastTimeRound = '';
        $tournamentMatch = TournamentMatch::findOrFail($matchId);
        
        $orders = array();
  
        $order = $tournamentMatch->order * 2;
        $orders[] = $order;
        $orders[] = $order + 1;

        $round = $tournamentMatch->round - 1;

        $lastTimePreviousRound = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.round', '=', $round)
            ->whereIn('tournament_matches.order', $orders);
        if( $typeDraw == 1){
            $lastTimePreviousRound->where('main_draw', 1);
        }else{
            $lastTimePreviousRound->where('back_draw', 1);
        }
        $lastTimePreviousRound = $lastTimePreviousRound->where( 'tournament_matches.category_id', '=', $tournamentMatch->category_id)
            ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->max('tournament_matches_date_court.date');
            
        return $lastTimePreviousRound;
        
    }

   /* public function assignAllOtherMatchesScheduleByCategory(string $categoryId, string $typeDraw){

        $category = Category::findOrFail($categoryId);
        $maxRound = TournamentMatch::where('category_id', '=', $categoryId);
        if( $typeDraw == 1){
            $maxRound->where('main_draw', 1);
        }else{
            $maxRound->where('back_draw', 1);
        }
        $maxRound = $maxRound->max('round');
        if( false == $maxRound){
            $maxRound = 0;
        }

        $timeBetweenMarchs = self::getMatchtimeIntervalBetweenMatchs($categoryId);
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)->whereNull('tournament_match_id')->orderBy('date')->get();
        
        $elements = DB::table('tournament_matches_date_court')
                ->select('tournament_match_id  as id')
                ->where('tournament_id', $category->tournament_id)
                ->whereNotNull('tournament_match_id')
                ->get();
        $scheduledMatches = array();
        foreach ($elements as $key => $value) {
            $scheduledMatches[] = $value->id;
        }
        
        $matchsAllOtherRound = TournamentMatch::where("category_id", $categoryId)->whereNotIn('id', $scheduledMatches)
            ->where("round", ">", 0)->where("round", '<', $maxRound);
        if( $typeDraw == 1){
            $matchsAllOtherRound->where('main_draw', 1);
        }else{
            $matchsAllOtherRound->where('back_draw', 1);
        }
        $matchsAllOtherRound = $matchsAllOtherRound->orderBy('id')->get();


       
        $cont = 0;
        foreach ($matchsAllOtherRound as $match) { 
            if($cont > count($viablesSchedules)){
                break;
            }
            // get date of previous round
            $previusMatch = self::getLastMatchsPreviousRound($match->id, $typeDraw);
            $arrayDate = explode(' ',$previusMatch);
            $arrayDay = explode('-',$arrayDate[0]);
            $arrayHour = explode(':',$arrayDate[1]);
            $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
            $endMatch->addMinutes($timeBetweenMarchs);
            $filtered_collection = $viablesSchedules->filter(function ($item) use($endMatch) {
                return $item->date > $endMatch && $item->tournament_match_id == null;
            })->first();
                $filtered_collection->update(['tournament_match_id' => $match->id]);
                
            $cont ++;

        }
    }

    */

    public function assignMatchScheduleTournament( $tournamentId ){
    
        $resp = collect([]);
    

        if(true == self::scheduleFinalsMatch($tournamentId)){
            $resp['schedule_finals'] = true;
        }

        if(true == self::assignMatchCoupleWithSchedule( $tournamentId)){
            $resp['schedule_couples_limited_hours'] = true;
        }
        

        $tournament = Tournament::findOrFail($tournamentId);
        
        foreach ($tournament->categories as $category) {
            $elements = DB::table('tournament_matches_date_court')
                ->select('tournament_match_id  as id')
                ->where('tournament_id', $tournamentId)
                ->whereNotNull('tournament_match_id')
                ->get();
            $ids = array();
            foreach ($elements as $key => $value) {
                $ids[] = $value->id;
            }

            //Asignamos los partidos consecutivamente de la primera ronda
            $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $tournament->id)->whereNull('tournament_match_id')->orderBy('date')->get();
            
            list($rounds, $headCouples) = self::getTotalRounds(count($category->couples));
            if( $headCouples == 0 || $category->type == 1 || $category->type == 2 ){
                $matchsFirstRound = TournamentMatch::where("category_id", $category->id)->whereNotIn('id', $ids)->where("round", 0)
                                ->where("main_draw", 1)->orderBy('id')->get();
            }else{
                $secoundRound = TournamentMatch::where("category_id", $category->id)->whereNotIn('id', $ids)->where("round", 1)
                    ->where("main_draw", 1)->orderBy('id', 'desc')->get();
                $firstRound = TournamentMatch::where("category_id", $category->id)->whereNotIn('id', $ids)->where("round", 0)
                                ->where("main_draw", 1)->whereNotNull('local_couple_id')->whereNotNull('visiting_couple_id')->orderBy('id', 'desc')
                                ->get();

                $matchsFirstRound = $firstRound;//->merge($secoundRound);              
            }
            
            $cont = 0;
            foreach ($matchsFirstRound as $match) { 
                if($cont > count($viablesSchedules)){
                    break;
                }
                $viablesSchedules[$cont]->update(['tournament_match_id' => $match->id]);
                $cont ++;
                $ids[] = $match->id;
            }
            

            // Asignamos los partidos de las rondas siguientes (no de la final)
            if($category->type == 3 || $category->type == 4 ) {
                self::assignMatchScheduleDraw($category);
            }

            if($category->type == 1 || $category->type == 2 || $category->type == 6) {
                self::assignMatchScheduleDraw($category);
            }
                
        }
        
        
        $resp['message'] = 200;
        return response()->json(
            $resp    
        );
    
    }


    
    


    public static function assignMatchScheduleDraw(Category $category, $typeDraw = 1){
   
        $elements = DB::table('tournament_matches_date_court')
            ->select('tournament_match_id  as id')
            ->where('tournament_id', $category->tournament_id)
            ->whereNotNull('tournament_match_id')
            ->get();
        $scheduledMatches = array();
        foreach ($elements as $key => $value) {
            $scheduledMatches[] = $value->id;
        }

        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
            ->whereNull('tournament_match_id')->orderBy('date')->get();

        // Add condiction where take the last match of the round 1 for the playoffs
        if( $typeDraw == 2){

            $lastMatchFirstRound = DB::table("tournament_matches")
                ->where('tournament_matches.deleted_at', NULL)
                ->where('tournament_matches.category_id', $category->id)
                ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
                ->where('tournament_matches_date_court.deleted_at', NULL)
                ->where('tournament_matches.round', '=', 1)
                ->where('tournament_matches.main_draw', '=', 1)
                ->max('tournament_matches_date_court.date');

             $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
                ->whereNull('tournament_match_id')
                ->whereDate('date', '>=', $lastMatchFirstRound)
                ->orderBy('date')->get();

        }


        $maxRound = TournamentMatch::where('category_id', '=', $category->id);
        if( $typeDraw == 1){
            $maxRound->where('main_draw', 1);
        }else{
            $maxRound->where('back_draw', 1);
        }
        $maxRound = $maxRound->max('round');

        if( ($category->type == 3 || $category->type == 4) && $maxRound > 1 ) {
            $maxRound = $maxRound - 1; // the first round schedule has been created
        }

        if($category->type == 2 ) {
            $maxRound = $maxRound + 1; // We have to make schedules for the playoffs
        }

        $matchRound = intdiv(count($viablesSchedules), $maxRound);
        $cont = 0;
        
        for ($i=1; $i <= $maxRound; $i++) { 
            $index = $cont * $matchRound + (intdiv($matchRound, 2));
            
            $matchsOtherRound = TournamentMatch::where("category_id", $category->id);

            if( $typeDraw == 1){
                $matchsOtherRound->where('main_draw', 1);
            }else{
                $matchsOtherRound->where('back_draw', 1);
            }

            $matchsOtherRound = $matchsOtherRound->whereNotIn('id', $scheduledMatches)->where("round", "=", $i)
            ->orderBy('id')
            ->get();
                
            $subIndex = 0;
            $flag = true;
            foreach ($matchsOtherRound as $match) { 
                if( $flag == true ){
                    $viablesSchedules[$index + $subIndex]->update(['tournament_match_id' => $match->id]); 
                    $subIndex++;
                    $flag = false;
                }else{
                    $viablesSchedules[$index - $subIndex]->update(['tournament_match_id' => $match->id]); 
                    $flag = true;
                }
            }
            $cont ++;
        }
    }


    /*
    public static function assignMatchScheduleDrawOld(Category $category){

        $elements = DB::table('tournament_matches_date_court')
            ->select('tournament_match_id  as id')
            ->where('tournament_id', $category->tournament_id)
            ->whereNotNull('tournament_match_id')
            ->get();
        $scheduledMatches = array();
        foreach ($elements as $key => $value) {
            $scheduledMatches[] = $value->id;
        }

        $maxRound = TournamentMatch::where('category_id', '=', $category->id)->where('main_draw', 1)->max('round');
        $timeBetweenMarchs = self::getMatchtimeIntervalBetweenMatchs($category->id);
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)->whereNull('tournament_match_id')->orderBy('date')->get();
        $matchsOtherRound = TournamentMatch::where("category_id", $category->id)
                ->where('main_draw', 1)->whereNotIn('id', $scheduledMatches)->where("round", ">", 0)
                ->where("round", '<', $maxRound)->orderBy('id')
                ->get();
        $cont = 0;
        foreach ($matchsOtherRound as $match) { 
            if($cont > count($viablesSchedules)){
                break;
            }
            // get date of previous round
            $order = intdiv($match->order, 2);
            $round = $match->round - 1;
            $previusMatch = DB::table('tournament_matches_date_court')
                ->where('tournament_matches_date_court.deleted_at', NULL)
                ->join('tournament_matches', 'tournament_matches_date_court.tournament_match_id', '=', 'tournament_matches.id')
                ->where('tournament_matches.deleted_at', NULL)
                ->where('tournament_matches.order', $order)
                ->where('tournament_matches.round', $round)
                ->where('tournament_matches.category_id', $category->id)
                ->select('date')
                ->first();
                if(!$previusMatch){
                    $previusMatch = DB::table('tournament_matches_date_court')
                        ->where('tournament_matches_date_court.deleted_at', NULL)
                        ->join('tournament_matches', 'tournament_matches_date_court.tournament_match_id', '=', 'tournament_matches.id')
                        ->where('tournament_matches.deleted_at', NULL)
                        ->where('tournament_matches.round', $round)
                        ->where('tournament_matches.category_id', $category->id)
                        ->orderBy('date', 'desc')
                        ->select('date')
                        ->first();
                }
                if( $previusMatch ){
                    $arrayDate = explode(' ',$previusMatch->date);
                    $arrayDay = explode('-',$arrayDate[0]);
                    $arrayHour = explode(':',$arrayDate[1]);
                    $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
                    $endMatch->addMinutes($timeBetweenMarchs);
                    $filtered_collection = $viablesSchedules->filter(function ($item) use($endMatch) {
                        return $item->date > $endMatch && $item->tournament_match_id == null;
                    })->first();
                    if( $filtered_collection != null){
                        $filtered_collection->update(['tournament_match_id' => $match->id]);     
                    }
                }
                
            $cont ++;

        }
    }

    */
    /*
    public static function assignMatchScheduleLeagues(Category $category){

        $elements = DB::table('tournament_matches_date_court')
            ->select('tournament_match_id  as id')
            ->where('tournament_id', $category->tournament_id)
            ->whereNotNull('tournament_match_id')
            ->get();
        $scheduledMatches = array();
        foreach ($elements as $key => $value) {
            $scheduledMatches[] = $value->id;
        }

        $maxRound = TournamentMatch::where('category_id', '=', $category->id)->where('main_draw', 1)->max('round');
        $matchsScheduledPending = TournamentMatch::where("category_id", $category->id)
                ->where('main_draw', 1)->whereNotIn('id', $scheduledMatches)
                ->orderBy('id')->get();
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
            ->whereNull('tournament_match_id')->orderBy('date')->get();
        
        $spaceBetweenMatch = intdiv($viablesSchedules->count(), $maxRound + 2);
        
        $cont = 1;
        for ($round = 1; $round <= $maxRound; $round++) {
            $tramo = $cont * $spaceBetweenMatch; 
            $matchsJourneyScheduledPending = TournamentMatch::where("category_id", $category->id)
                ->where('main_draw', 1)->whereNotIn('id', $scheduledMatches)
                ->where('round', $round)
                ->orderBy('id')->get();
            foreach ($matchsJourneyScheduledPending as $match) {  
                $tramo ++;
                $viablesSchedules[$tramo]->update(['tournament_match_id' => $match->id]);
            }
            $cont ++;
        }
        


        return response()->json([
            'message' => 200,
            'max_rounds' => $maxRound,
            'match_pending' => $matchsScheduledPending->count(),
            'viables_schedules_total' => $viablesSchedules->count(),
            'spaceBetweenMatch' => $spaceBetweenMatch,
            'viables_schedules' => $viablesSchedules,
        ]);
        
    }

    */
    


    public static function getMatchtimeIntervalBetweenMatchs(string $category_id){

        $category = Category::findOrFail($category_id);
        $tournament = Tournament::findOrFail($category->tournament_id);
        $schedule = TournamentScheduleDayHour::where('tournament_id', $tournament->id)->orderBy('date')->get();
        $totalFinals = count($tournament->categories);
        $totalCourts = Court::where('club_id', $tournament->club_id )->where('sport_type', $tournament->sport_type)->count();
        $totalMatchsCategory = TournamentMatch::where('category_id', $category_id)->count() -1 ;
        $maxRound = TournamentMatch::where('category_id', '=', $category_id)->max('round');

        if($maxRound == 0){
            $maxRound = 1;
        }

        if($category->type == 6){
            $maxRound = $maxRound * 2;
        }

        $extraRound = 2;
        $n = 1;
        for ($i=0; $i <= $maxRound; $i++) { 
            $n = $n * 2;
        }
        if( $n == count($category->couples)){
            $extraRound = 2;
        }

        $totalMins = 0;
        foreach ($schedule as $scheduleDay) {
            $arrayStart = explode(':',$scheduleDay->opening_time);
            $arrayEnd = explode(':',$scheduleDay->closing_time);
            $hours = $arrayEnd[0] - $arrayStart[0];
            $mins = $hours * 60;
            $mins = $mins + ($arrayEnd[1] - $arrayStart[1]);
            $totalMins = $totalMins + $mins;
        }
        $totalMins = $totalMins * $totalCourts;
        $timeToFinals = $totalFinals/$totalCourts;
        
        return response()->json([
            'message' => 200,
            'schedule' => $schedule,
            'totalCourts' => $totalCourts,
            'totalMins' => $totalMins,
            'maxRounds' => $maxRound,
            'time_between' => $totalMins/($maxRound + 1),
            'extraRounds' => $extraRound,
            'maxRound' => $maxRound,
            'n' => $n,
            'couples' => count($category->couples),
            'totalMins_result' => $totalMins/($maxRound + $extraRound ) 
        ]);

        return $totalMins/($maxRound + $extraRound);//$totalMins / $totalMatchsCategory;
    }


    public function crossing_finger( $torunamentId){

        $user = User::findOrFail(1369);
        // Mail::to('jorge.zancada.moreno@gmail.com')->queue( new SendEmail('confirm_email_message', 'Verifica tu correo', $client));
        $club = Club::findOrFail($user->club_id);
        $token = Str::random(30);
        $confirmEmail = ConfirmEmail::create([
            'club_id' => $user->club_id,
            'email' => $user->email,
            'user_id' => $user->id,
            'token' => $token
        ]);

        $client['verify_link'] = env('HOME_URL').'verify-email/'.$token;
        $client['club_name'] = '';

        $sendEmail = Mail::to($user->email)->send( new SendEmail('welcome_message', 'Verifica tu correo', $client)); 
        return response()->json( [
            'message' => 200
        ]);
    
       
    }
    


    
    public function assignMatchCoupleWithSchedule( $tournamentId ){

        $tournament =  Tournament::findOrFail($tournamentId);
        $this->authorize('update', $tournament);

        $limitedScheduleCouples = DB::select("SELECT SUM(q1.total_limitations_hours) AS total, q1.id FROM (
            SELECT COUNT(*) AS total_limitations_hours, t.id FROM tournament_matches t
                INNER JOIN couples c ON c.id = t.visiting_couple_id AND t.local_couple_id IS NOT NULL
                INNER JOIN couple_not_play_hour_tournaments cnp ON c.id = cnp.couple_id
                WHERE t.tournament_id = ? AND t.deleted_at IS NULL
                GROUP BY cnp.couple_id
            UNION 
            SELECT COUNT(*) AS total_limitations_hours, t.id FROM tournament_matches t
                INNER JOIN couples c ON c.id = t.local_couple_id AND t.visiting_couple_id IS NOT NULL
                INNER JOIN couple_not_play_hour_tournaments cnp ON c.id = cnp.couple_id
                WHERE t.tournament_id = ? AND t.deleted_at IS NULL
                GROUP BY cnp.couple_id) AS q1 GROUP BY q1.id
            ORDER BY total desc; ", [$tournamentId, $tournamentId ]);

        $scheduleNotPlay = collect([]);
        foreach ($limitedScheduleCouples as $match) {
            $tournamentMatch = TournamentMatch::findOrFail($match->id);
            if($tournamentMatch->visiting_couple != null){
                foreach ($tournamentMatch->visiting_couple->scheduleNotPlay as $schedule) {
                    $arrayDate = explode('-',$schedule->date);
                    $arrayHourStart = explode(':',$schedule->start_time);
                    $arrayHourEnd = explode(':',$schedule->end_time);
                    $dateStart = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourStart[0], $arrayHourStart[1], 0);
                    $dateEnd = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourEnd[0], $arrayHourEnd[1], 0);
                    $scheduleNotPlay->push([
                        'match_id' => $match->id,
                        'start' => $dateStart,
                        'end' => $dateEnd,
                    ]);
                }
            }
            if($tournamentMatch->local_couple != null){
                foreach ($tournamentMatch->local_couple->scheduleNotPlay as $schedule) {
                    $arrayDate = explode('-',$schedule->date);
                    $arrayHourStart = explode(':',$schedule->start_time);
                    $arrayHourEnd = explode(':',$schedule->end_time);
                    $dateStart = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourStart[0], $arrayHourStart[1], 0);
                    $dateEnd = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourEnd[0], $arrayHourEnd[1], 0);
                    $scheduleNotPlay->push([
                        'match_id' => $match->id,
                        'start' => $dateStart,
                        'end' => $dateEnd,
                    ]);
                }
            }
        }

        $grouped_schedule = array();
        //$matchsId = array();
        foreach ($scheduleNotPlay as $element) {
            $grouped_schedule[$element['match_id']][] = $element;
          //  $matchsId[] = $element['match_id'];
        }
        //$matchsId = array_unique($matchsId); 

        $timePerMatch = $tournament->time_per_match;
        foreach ($grouped_schedule as $key => $scheduleNotPlay) {
            $findHourAndCourt = DB::table("tournament_matches_date_court")
            ->where('tournament_matches_date_court.tournament_id', $tournamentId)
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->where('tournament_matches_date_court.tournament_match_id', NULL)
            ->where(
                function($query) use($scheduleNotPlay, $timePerMatch){
                    foreach ($scheduleNotPlay as $schedule) {
                        $arrayDate = explode(' ',$schedule['start']);
                        $arrayDay = explode('-',$arrayDate[0]);
                        $arrayHour = explode(':',$arrayDate[1]);
                        $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
                        $endMatch->subMinutes($timePerMatch);
                        $query
                            ->whereNotBetween('tournament_matches_date_court.date', [$schedule['start'], $schedule['end']])
                            ->whereNotBetween('tournament_matches_date_court.date', [$endMatch, $schedule['end']]);
                    }
                    return $query;
                 })
            ->orderBy("date", "asc")->first();
            if($findHourAndCourt){
                $matchCourt = TournamentMatchDateCourt::findOrFail($findHourAndCourt->id);
                $matchCourt->update(['tournament_match_id' => $key]);
            }
        }
        return true;
    }

    public function assignMatchCoupleWithScheduleByCategory( $categoryId ){

        $category =  Category::findOrFail($categoryId);
        $tournament = Tournament::findOrFail($category->tournament_id);


        $limitedScheduleCouples = DB::select("SELECT SUM(q1.total_limitations_hours) AS total, q1.id FROM (
            SELECT COUNT(*) AS total_limitations_hours, t.id FROM tournament_matches t
                INNER JOIN couples c ON c.id = t.visiting_couple_id
                INNER JOIN couple_not_play_hour_tournaments cnp ON c.id = cnp.couple_id
                WHERE t.category_id = ? AND t.deleted_at IS NULL
                GROUP BY cnp.couple_id
            UNION 
            SELECT COUNT(*) AS total_limitations_hours, t.id FROM tournament_matches t
                INNER JOIN couples c ON c.id = t.local_couple_id
                INNER JOIN couple_not_play_hour_tournaments cnp ON c.id = cnp.couple_id
                WHERE t.category_id = ? AND t.deleted_at IS NULL
                GROUP BY cnp.couple_id) AS q1 GROUP BY q1.id
            ORDER BY total desc; ", [$categoryId, $categoryId ]);

        $scheduleNotPlay = collect([]);
        foreach ($limitedScheduleCouples as $match) {
            $tournamentMatch = TournamentMatch::findOrFail($match->id);
            if($tournamentMatch->visiting_couple != null){
                foreach ($tournamentMatch->visiting_couple->scheduleNotPlay as $schedule) {
                    $arrayDate = explode('-',$schedule->date);
                    $arrayHourStart = explode(':',$schedule->start_time);
                    $arrayHourEnd = explode(':',$schedule->end_time);
                    $dateStart = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourStart[0], $arrayHourStart[1], 0);
                    $dateEnd = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourEnd[0], $arrayHourEnd[1], 0);
                    $scheduleNotPlay->push([
                        'match_id' => $match->id,
                        'start' => $dateStart,
                        'end' => $dateEnd,
                    ]);
                }
            }
            if($tournamentMatch->local_couple != null){
                foreach ($tournamentMatch->local_couple->scheduleNotPlay as $schedule) {
                    $arrayDate = explode('-',$schedule->date);
                    $arrayHourStart = explode(':',$schedule->start_time);
                    $arrayHourEnd = explode(':',$schedule->end_time);
                    $dateStart = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourStart[0], $arrayHourStart[1], 0);
                    $dateEnd = Carbon::create($arrayDate[0], $arrayDate[1], $arrayDate[2], $arrayHourEnd[0], $arrayHourEnd[1], 0);
                    $scheduleNotPlay->push([
                        'match_id' => $match->id,
                        'start' => $dateStart,
                        'end' => $dateEnd,
                    ]);
                }
            }
        }

        $grouped_schedule = array();
        //$matchsId = array();
        foreach ($scheduleNotPlay as $element) {
            $grouped_schedule[$element['match_id']][] = $element;
          //  $matchsId[] = $element['match_id'];
        }
        //$matchsId = array_unique($matchsId); 
       

        $timePerMatch = $tournament->time_per_match;
        foreach ($grouped_schedule as $key => $scheduleNotPlay) {
            $findHourAndCourt = DB::table("tournament_matches_date_court")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->where('tournament_matches_date_court.tournament_match_id', NULL)
            ->where(
                function($query) use($scheduleNotPlay, $timePerMatch){
                    foreach ($scheduleNotPlay as $schedule) {
                        $arrayDate = explode(' ',$schedule['start']);
                        $arrayDay = explode('-',$arrayDate[0]);
                        $arrayHour = explode(':',$arrayDate[1]);
                        $endMatch = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0);
                        $endMatch->subMinutes($timePerMatch);
                        $query
                            ->whereNotBetween('tournament_matches_date_court.date', [$schedule['start'], $schedule['end']])
                            ->whereNotBetween('tournament_matches_date_court.date', [$endMatch, $schedule['end']]);
                    }
                    return $query;
                 })
            ->orderBy("date", "asc")->first();
            if($findHourAndCourt){
                $matchCourt = TournamentMatchDateCourt::findOrFail($findHourAndCourt->id);
                $matchCourt->update(['tournament_match_id' => $key]);
            }
        }
        return true;
    }

    public function scheduleFinalsMatch( $tournamentId ){

        $tournament = Tournament::findOrFail($tournamentId);
        $this->authorize('update', $tournament);

        $matchesTournamentFinals = array();
        foreach ($tournament->categories as $category) {
            if($category->type == 3 || $category->type == 4){
                $match = TournamentMatch::where('category_id', $category->id)->where('main_draw', 1)
                    ->orderBy('round', 'desc')->first();
                    if($match){
                        $matchesTournamentFinals[] = $match->id;
                    }
                }
        }

        
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $tournamentId)
                ->whereNull('tournament_match_id')
                ->orderBy("date", "desc")
                ->get();
        
        for ($i=0; $i < count($matchesTournamentFinals); $i++) { 
            $viablesSchedules[$i]->update(['tournament_match_id' => $matchesTournamentFinals[$i]]);
        }
        
        return true;

    }

    public function checkConfigureTournament(Tournament $tournament){

        $this->authorize('configure', $tournament);

        $totalMatchsFinals = $tournament->categories->count() * 2;

        $totalGapsTournaments = TournamentMatchDateCourt::where("tournament_id", $tournament->id)->count();
        $totalMatchsTournament = DB::select("SELECT count(*) as total FROM tournament_matches  
                                WHERE deleted_at IS NULL AND tournament_id = ? AND (round > 0 OR 
                                (round = 0 AND local_couple_id IS NOT NULL AND visiting_couple_id IS NOT NULL))  
                                ", [$tournament->id]);

        // Check if there are back-draw categories to plus to matchesTournament
        $matchsBackDraw = 0;
        $playOffMatchs = 0;
        foreach ($tournament->categories as $category) {
            if( $category->type == 3){
                $totalMatchsMainDraw = TournamentMatch::where('category_id', $category->id)->count();
                $matchsBackDraw += intdiv($totalMatchsMainDraw, 2) + 1;
            }
            if( $category->type == 2){
                $playOffMatchs += 3;
            }
        }
        
        if( $totalGapsTournaments < $totalMatchsFinals + $totalMatchsTournament[0]->total + $matchsBackDraw + $playOffMatchs){
            self::deleteDraw($tournament->id);
            return false;
        }else{
           return true;
        }

    }
    

    public function configureTournament(string $tournamentId){

        $tournament = Tournament::findOrFail($tournamentId);

        $this->authorize('configure', $tournament);
        
        self::defineAvailableCourtsHours($tournamentId);

        foreach ($tournament->categories as $category) {
            if( $category->type == 3){ // Normal Draw and Back Draw
                self::createDraw($category->id, 1);
            }elseif( $category->type == 4){ // Normal Draw 
                self::createDraw($category->id, 1);
            }elseif($category->type == 1){ // Normal League
                self::createLeagueTournament($category->id);
            }elseif($category->type == 2){ // Two Leagues and Playoff
                self::createTwoLeaguesTournament($category->id);
            }elseif($category->type == 6){ // League with two legs
                self::createLeaguesTwoLegsTournament($category->id);
            }
        }

        $enoughCourts = false;
        $enoughCourts = self::checkConfigureTournament($tournament);     
        if( false == $enoughCourts){
            return response()->json([
                'message' => 403
            ]);
        }else{

            self::directNextRoundCouple($tournamentId);
            self::assignMatchScheduleTournament($tournamentId);
    
            foreach ($tournament->categories as $category) {
                if($category->type == 2){
                    self::createPlayOff($category);
                }
            }
            
            $tournament->update([
                'draw_generated' => '1'
            ]);
    
            return response()->json([
                'message' => 200
            ]);

        }

       

    }


    public function getTotalRounds( $totalCouples ){

        $cont = 1;
        $rounds = 0;
        while( $cont < $totalCouples){
            $cont = $cont * 2;
            $rounds ++;
        }

        $headCouples = $cont - $totalCouples;

        return [$rounds, $headCouples];

    }

    public function createLeagueTournament( string $category_id){  //LEAGUE
        
        $category = Category::findOrFail($category_id);
        $teams = Couple::where('category_id', $category_id)->inRandomOrder()->get();
        $totalTeams = $teams->count();

        self::createSimpleLeague($category, $teams, 0);

    }

    public function createLeaguesTwoLegsTournament(string $category_id){  // TWO LEGS LEAGUE 

        $category = Category::findOrFail($category_id);
        $teams = Couple::where('category_id', $category_id)->inRandomOrder()->get();
        // 1 LEG
        self::createSimpleLeague($category, $teams, 0);

        $totalCouples = count($teams);

        while($totalCouples > 0) {
            $totalCouples --;
            $revertTeams[] = $teams[$totalCouples];
        }

        // SECOND LEG
        $maxRound = TournamentMatch::where('category_id', '=', $category_id)->max('round') + 1;
        self::createSimpleLeague($category, $revertTeams, 0, $maxRound);

        return response()->json([
            'message' => 200,
            'team_ida' => $teams,
            'team_vuelta' => $revertTeams
        ]);
        
    }


    public function createSimpleLeague( Category $category ,$couples, $leagueNumber = 0, $secondLeg = 0 ){

        $totalTeams = count($couples);

        $idTeamsArray = array();
        foreach ($couples as $value) {
            $idTeamsArray[] = $value->id;
        }


        if( $totalTeams % 2 == 1){ // numbers of couples is odd
            $idTeamsArray[] = -1;
        }

        $totalTeams = count($idTeamsArray);
        $cont = 1;

        for( $journey = 1; $journey < $totalTeams; $journey++){
            $matching = [];
            $rand = 0;
            for( $i = 0; $i < $totalTeams / 2; $i++){
                $checkValidTeam1 = $i + 1;
                $checkValidTeam2 = $totalTeams / 2 + 1;
                $team1 = $idTeamsArray[$i];
                $team2 = $idTeamsArray[$totalTeams - 1 - $i];
                if ($checkValidTeam1 != $totalTeams && ($checkValidTeam2 != $totalTeams || $totalTeams == 2) && $team1 != -1 && $team2 != -1 && $team1 != $team2) {
                    //$rand = rand(0, 1);
                    $item = [
                        'tournament_id' => $category->tournament_id,
                        'category_id' => $category->id,
                        'round' => ($journey + $secondLeg) - 1,
                        'order' => 0,
                        'league_number' => 0
                    ];
                    $item['main_draw'] = 1;
                    if( $secondLeg > 0){
                        $item['is_second_leg'] = 1;
                    }
                    $rand ++;
                    if( $rand % 2 == 0){
                        $item['local_couple_id'] = $team1;
                        $item['visiting_couple_id'] = $team2;
                    }else{
                        $item['local_couple_id'] = $team2;
                        $item['visiting_couple_id'] = $team1;
                    }
                    if($leagueNumber != 0){
                        $item['league_number'] = $leagueNumber;
                    }
                    TournamentMatch::create($item);
                    $matching[] = [$team1, $team2];
                }
            }
            array_splice($idTeamsArray, 1, 0, array_pop($idTeamsArray));
            $calendar[] = $matching;
            $cont ++;
        }

    }

    public function createTwoLeaguesTournament( string $category_id){  //LEAGUE + PLAYOFF

        $category = Category::findOrFail($category_id);
        $couples = DB::select("SELECT 0 as 'seed',c.id, c.name, SUM(p.total_points) AS points FROM couples c 
                    INNER JOIN couple_players cp ON cp.couple_id = c.id AND cp.deleted_at IS NULL AND c.deleted_at IS NULL AND c.category_id = ?
                    INNER JOIN users p ON p.id = cp.user_id
                    GROUP BY c.id
                    ORDER BY points desc;", [$category_id]);
         
        foreach ($couples as $key => $couple) {
            $couple->seed = $key + 1;
        }
        $totalCouples = count($couples);

        $league1 = [];
        $league2 = [];
        for ($i=0; $i < $totalCouples; $i++) { 
            if( $i % 2 == 1){
                $league2[] = $couples[$i];
                $affected = DB::table('couples')->where('id', $couples[$i]->id)->update(['league_number' => 2]);
            }else{
                $league1[] = $couples[$i];
                $affected = DB::table('couples')->where('id', $couples[$i]->id)->update(['league_number' => 1]);
            }
        }



        self::createSimpleLeague($category, $league1, 1);
        self::createSimpleLeague($category, $league2, 2);
    

        return response()->json([
            'message' => 200,
            'league_1' => $league1,
            'league_2' => $league2,
            'total_couples' => $totalCouples
        ]);

    }


    

    public function createDraw( string $category_id, int $drawType = 1){  //DRAW
    
        $category = Category::findOrFail($category_id);
       // $couples = Couple::where('category_id', $category->id)->inRandomOrder()->get();
        if( $drawType == 1){
            $couples = DB::select("SELECT 0 as 'seed',c.id, c.name, SUM(p.total_points) AS points FROM couples c 
                    INNER JOIN couple_players cp ON cp.couple_id = c.id AND cp.deleted_at IS NULL AND c.deleted_at IS NULL AND c.category_id = ?
                    INNER JOIN users p ON p.id = cp.user_id
                    GROUP BY c.id
                    ORDER BY points desc;", [$category_id]);
        }else{ //Couples backdraw
            $couples = DB::select("SELECT 0 as 'seed',c.id, c.name, SUM(p.total_points) AS points FROM couples c 
                INNER JOIN couple_players cp ON cp.couple_id = c.id  AND c.to_back_draw = 1 AND cp.deleted_at IS NULL AND c.deleted_at IS NULL AND c.category_id = ?
                INNER JOIN users p ON p.id = cp.user_id
                GROUP BY c.id
                ORDER BY points desc;", [$category_id]);
        }

        foreach ($couples as $key => $couple) {
            $couple->seed = $key + 1;
        }

       
        $headCouples = 0;
        list($rounds, $headCouples) = self::getTotalRounds(count($couples));

        $couplesHeader = [];
        for($i = 0; $i<$headCouples; $i++){
            $couplesHeader[] = $couples[$i]->id;
        }
        $n = 1;
        for ($i=0; $i < $rounds; $i++) { 
           $n = $n * 2;
        }

        $positionCouples = self::generatePositionDraw($n);
        $drawAsignet = self::asignCoupleDraw($couples, $positionCouples);
        $matchs = self::generateDraw($drawAsignet);

       
        
        $round = 0;
        $totalMatchs = count($couples);
        if( $headCouples == 0){
            $totalMatchs = intdiv($totalMatchs, 2);
        }
        for ($i=0; $i < $rounds; $i++) { 
            $order = 0;
            if($round == 0){
                foreach ($matchs as $match) {
                    $item = [
                        'tournament_id' => $category->tournament_id,
                        'category_id' => $category->id,
                        'local_couple_id' => $match['local_couple'] != null ? $match['local_couple']->id : null,
                        'visiting_couple_id' => $match['visiting_couple'] != null ? $match['visiting_couple']->id : null,
                        'round' => $round,
                        'order' => $order
                    ];
                    if( $drawType == 1){
                        $item['main_draw'] = 1;
                    }
                    if( $drawType == 2){
                        $item['back_draw'] = 1;
                    }
                    TournamentMatch::create($item);
                    $order ++;
                }
            }else{
                $totalMatchs = intdiv($totalMatchs, 2);
                for ($j = 0; $j < $totalMatchs; $j ++ ) {
                    $item = [
                        'tournament_id' => $category->tournament_id,
                        'category_id' => $category->id,
                        'round' => $round,
                        'order' => $order
                    ];
                    if( $drawType == 1){
                        $item['main_draw'] = 1;
                    }
                    if( $drawType == 2){
                        $item['back_draw'] = 1;
                    }
                    TournamentMatch::create($item);
                    $order ++;
                }
            }
            $round ++;
        }
    }


    function generatePositionDraw($n) {
        if ($n == 1) {
            return [1];
        }
    
        // Generar posiciones para la mitad del cuadro
        $mitad = self::generatePositionDraw($n / 2);
    
        // Reflejar esas posiciones en la otra mitad del cuadro
        $posiciones = [];
        foreach ($mitad as $pos) {
            $posiciones[] = $pos;
            $posiciones[] = $n + 1 - $pos;
        }
    
        return $posiciones;
    }
    
    
    function asignCoupleDraw($jugadores, $posicionesSemillas) {
        $semillaAJugador = [];
        foreach ($jugadores as $jugador) {
            $semillaAJugador[$jugador->seed] = $jugador;
        }
    
        $cuadro = [];
        foreach ($posicionesSemillas as $pos) {
            if (isset($semillaAJugador[$pos])) {
                $cuadro[] = $semillaAJugador[$pos];
            } else {
                $item = new stdClass();
                $item ->seed = null;
                $item->id = 'Bye';
                $item->name = 'Bye';
                $item->points = 0;
                $cuadro[] = $item;
            }
        }
        return $cuadro;
    }
    
    function generateDraw($cuadroAsignado) {
        $partidos = [];
        for ($i = 0; $i < count($cuadroAsignado); $i += 2) {
            $jugador1 = $cuadroAsignado[$i];
            $jugador2 = $cuadroAsignado[$i + 1];
    
            if (is_null($jugador1->seed)) {
                $partidos[] = ['local_couple' => null, 'visiting_couple' => $jugador2];
            } elseif (is_null($jugador2->seed)) {
                $partidos[] = ['local_couple' => $jugador1, 'visiting_couple' => null];
            } else {
                $partidos[] = ['local_couple' => $jugador1, 'visiting_couple' => $jugador2];
            }
        }
        return $partidos;
    }


    

    public function directNextRoundCouple( string $tournamentId){

        $tournamentMatches = DB::select("SELECT * FROM tournament_matches WHERE deleted_at IS NULL AND round = 0 AND tournament_id = ?
            AND ( (local_couple_id IS NULL AND  visiting_couple_id IS NOT NULL) OR (local_couple_id IS NOT NULL AND  visiting_couple_id IS NULL))", [$tournamentId]);
            
        foreach ($tournamentMatches as $match) {
            $nextRound = $match->round + 1;
            $order = intdiv($match->order, 2);
            $rest = fmod($match->order , 2);
            $matchTournament = TournamentMatch::where( 'tournament_id', '=', $match->tournament_id)
                                ->where('round', '=', $nextRound)
                                ->where('order', '=', $order)
                                ->where('category_id', $match->category_id)
                                ->first();
            if($matchTournament){
                if( $rest == 0){
                    if( $match->local_couple_id == null){
                        $matchTournament->update(['local_couple_id' => $match->visiting_couple_id]);
                    }else{
                        $matchTournament->update(['local_couple_id' => $match->local_couple_id]);
                    }
                }else{
                    if( $match->local_couple_id == null){
                        $matchTournament->update(['visiting_couple_id' => $match->visiting_couple_id]);
                    }else{
                        $matchTournament->update(['visiting_couple_id' => $match->local_couple_id]);
                    }
                }
            }
        }
        
    }

    public function directNextBackDrawCategory( string $categoryId){

        $tournamentMatches = DB::select("SELECT * FROM tournament_matches WHERE deleted_at IS NULL AND round = 0 AND category_id = ? AND back_draw = 1
            AND ( (local_couple_id IS NULL AND  visiting_couple_id IS NOT NULL) OR (local_couple_id IS NOT NULL AND  visiting_couple_id IS NULL))", [$categoryId]);
            
        foreach ($tournamentMatches as $match) {
            $nextRound = $match->round + 1;
            $order = intdiv($match->order, 2);
            $rest = fmod($match->order , 2);
            $matchTournament = TournamentMatch::where( 'tournament_id', '=', $match->tournament_id)
                                ->where('round', '=', $nextRound)
                                ->where('order', '=', $order)
                                ->where('back_draw', '=', 1)
                                ->where('category_id', $categoryId)
                                ->first();
            if($matchTournament){
                if( $rest == 0){
                    if( $match->local_couple_id == null){
                        $matchTournament->update(['local_couple_id' => $match->visiting_couple_id]);
                    }else{
                        $matchTournament->update(['local_couple_id' => $match->local_couple_id]);
                    }
                }else{
                    if( $match->local_couple_id == null){
                        $matchTournament->update(['visiting_couple_id' => $match->visiting_couple_id]);
                    }else{
                        $matchTournament->update(['visiting_couple_id' => $match->local_couple_id]);
                    }
                }
            }
        }
        

        return response()->json([
            'message' => 200,
            'affected' => $tournamentMatches
        ]);

    }



    public function deleteDraw(string $tournamentId){

        $tournament = Tournament::findOrFail($tournamentId);
        $this->authorize('update', $tournament);
        
        $affected = TournamentMatchDateCourt::where('tournament_id', $tournamentId)
            ->delete();//update(['tournament_match_id' => NULL]);
        
        $deleted =TournamentMatch::where('tournament_id', $tournamentId)->delete();
        
        $deleted = TournamentCouplesClasification::where('tournament_id', $tournamentId)->delete();

        $affected = DB::table('couples')->where('tournament_id', $tournamentId)->update(['matches_played' => 0, 'to_back_draw' => 0]);

        $tournament->update([
            'draw_generated' => '0'
        ]);

        return response()->json([
            'message' => 200,
            'affected' => $affected,
            'removed' => $deleted
        ]);
    }


    public static function getDraw(string $id, string $type){
        Carbon::setLocale('auto');
        // GET MATCHS
        $maxRound = 0;
        //$matchesCategory = TournamentMatch::where('category_id', $id)->orderBy("round", "desc")->get();
        $category = Category::findOrFail($id);

        $tournament = Tournament::where('id', $category->tournament_id)->first();
        //$this->authorize('view', $tournament);

        if($type == 'main'){
            $matchesCategory = DB::table("tournament_matches")
                    ->where('tournament_matches.deleted_at', NULL)
                    ->where('tournament_matches.league_number', NULL)
                    ->where('tournament_matches.category_id', $id)
                    ->leftJoin('tournament_matches_date_court', 'tournament_matches.id', '=', 'tournament_matches_date_court.tournament_match_id')
                    ->leftJoin('courts', 'tournament_matches_date_court.court_id', 'courts.id')
                    ->where('tournament_matches_date_court.deleted_at', NULL)
                    ->select('tournament_matches.*', 'tournament_matches_date_court.date', DB::raw("courts.name AS court_name"))
                    ->where('tournament_matches.main_draw', 1)
                    ->get();
        }else{
            $matchesCategory = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.league_number', NULL)
            ->where('tournament_matches.category_id', $id)
            ->leftJoin('tournament_matches_date_court', 'tournament_matches.id', '=', 'tournament_matches_date_court.tournament_match_id')
            ->join('courts', 'tournament_matches_date_court.court_id', 'courts.id')
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->select('tournament_matches.*', 'tournament_matches_date_court.date', DB::raw("courts.name AS court_name"))
            ->where('tournament_matches.back_draw', 1)
            ->get();
        }

        $matches = $matchesCategory->map(function($item) use(&$maxRound, $category, $tournament){
            if( $item->round > $maxRound){
                $maxRound = $item->round;
            }
            $scores[] = [
                'mainScore' => ''
            ];
            $scores[] = [
                'mainScore' => ''
            ];
            $scores[] = [
                'mainScore' => ''
            ];
            if( $tournament->sport_type == 1 || $tournament->sport_type == 2){
                list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id);
            }else{
                list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id, true);
            }
            if( $item->local_couple_id != null ){
                $localPlayer = (string)$item->local_couple_id;
            }else{
                if($item->round == 0 ){
                    $localPlayer = 'WO';
                }else{
                    $localPlayer = '0';
                }
            }
            if( $item->visiting_couple_id != null ){
                $visitingPlayer = (string)$item->visiting_couple_id;
            }else{
                if($item->round == 0 ){
                    $visitingPlayer = 'WO';
                }else{
                    $visitingPlayer = '0';
                }
            }
            $sides[] = [
                'contestantId' => $localPlayer,
                'scores' => $scoresLocal,
                'isWinner' => $localWinner
            ];
            $sides[] = [    
                'contestantId' => $visitingPlayer,
                'scores' => $scoresVisiting,
                'isWinner' => $visitingWinner
            ];
            if( $item->date ){
                $date = Carbon::parse($item->date)->format('N H:i\h jS \of F');
            }else{
                if( $item->round >  0){
                    $date = 'ERROR';
                }else{
                    $date = '';
                }
            }
            return [
                "match_id" => $item->id,
                "roundIndex" => $item->round,
                "order" => $item->order,
                'time' =>  $date, //N l jS \of F Y h:i
                'sides' => $sides,
                'court' => $item->court_name
            ];
        });

        // GET PLAYERS
        $couples = Couple::where('category_id', $id)->get();
        $players =  $couples->map(function($item) {
            return [
                "id" => $item->id,
                'players' => $item->players->map(function($player) use($item){
                    return [
                        "title" => $player->user->name.' '.$player->user->surname
                    ];  
                })
            ];
        });
        $playerNoname[] = [
            'title' => '&nbsp;-'
        ];
        $playerWOName[] = [
            'title' => $category->type == 3 || $category->type == 4 ? 'BYE' : '- &nbsp; - &nbsp; -'
        ];
        $players[] = [
            "id" => 0,
            'players' => $playerNoname
        ];

        $players[] = [
            "id" => 'WO',
            'players' => $playerWOName
        ];

        $contestant = array();
        foreach ($players as $key => $value) {
            $contestant[$value['id']] = $value;
        }

        return response()->json([
            'message' => 200,
            'contestants' => $contestant,
            'matches' => $matches,
            'max_round' => $maxRound,
            'category_name' => $category->name
        ]);
    }

    public function getMatchesSimpleLeague(string $id){

        $category = Category::findOrFail($id);

        $tournament = Tournament::where('id', $category->tournament_id)->first();
        $this->authorize('view', $tournament);

        $matchesCategory = DB::table("tournament_matches")
                    ->where('tournament_matches.deleted_at', NULL)
                    ->where('tournament_matches.category_id', $id);
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

        $maxRound = TournamentMatch::where('category_id', '=', $id)->max('round');
        
        $matches =  $matchesCategory->map(function($item) use($tournament) {
            if( $tournament->sport_type == 1 || $tournament->sport_type == 2){
                list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id);
            }else{
                list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id, true);
            }
            
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

        return response()->json([
            'message' => 200,
            'match_type' => $category->match_type,
            'total_jorneys' => $maxRound,
            'matches' => $matches
        ]);
    }



    public function getClasification( $categoryId ){

        $category = Category::findOrFail($categoryId);

        $tournament = Tournament::where('id', $category->tournament_id)->first();
        $this->authorize('view', $tournament);

        $clasification = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->leftJoin("tournament_couples_clasifications","couples.id", "=", "tournament_couples_clasifications.couple_id")
            ->where('tournament_couples_clasifications.deleted_at', NULL)
            ->where('couples.category_id', $categoryId)
            ->select('tournament_couples_clasifications.*', 'couples.name', 'couples.league_number', DB::raw("couples.id as couple_id"))
            ->orderBy('tournament_couples_clasifications.total_points', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_won', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_lost', 'asc')
            ->orderBy('tournament_couples_clasifications.games_avg', 'desc')
            ->orderBy('couples.name', 'asc')
            ->get();  

        $matches =  $clasification->map(function($item) {
            
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
                'couple_players_name' => $couplePlayersName,
            ];
        });

        return response()->json([
            'message' => 200,
            'ranking' => $matches,
            'category_data' => $category,
            'sport_type' => $tournament->sport_type
        ]);

    }

    public static function createPlayOff( Category $category){

        $lastMatchOfGroups = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.category_id', $category->id)
            ->whereNotNull('league_number')
            ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->max('tournament_matches_date_court.date');

        $lastMatchOfGroupsFormat = Carbon::parse($lastMatchOfGroups)->format("Y-m-d");
        $viablesSchedules = TournamentMatchDateCourt::where('tournament_id', $category->tournament_id)
            ->whereNull('tournament_match_id')
            ->where('date', '>=', $lastMatchOfGroups)
            ->orderBy('date')->get();
        $indexSemifinal = intdiv($viablesSchedules->count(), 2);
        $indexSchedulesFinal = $viablesSchedules->count() - 1;
        $indexSchedulesSemifinal = intdiv($viablesSchedules->count(), 2);

        $item = [
            'tournament_id' => $category->tournament_id,
            'category_id' => $category->id,
            'local_couple_id' => null,
            'visiting_couple_id' => null,
            'main_draw' => 1,
            'round' => 0,
            'order' => 0
        ];
        $match = TournamentMatch::create($item);
        $viablesSchedules[$indexSemifinal]->update(['tournament_match_id' => $match->id]);

        $item = [
            'tournament_id' => $category->tournament_id,
            'category_id' => $category->id,
            'local_couple_id' => null,
            'visiting_couple_id' => null,
            'main_draw' => 1,
            'round' => 0,
            'order' => 1
        ];
        $match = TournamentMatch::create($item);
        $viablesSchedules[$indexSemifinal + 1]->update(['tournament_match_id' => $match->id]);

        $item = [
            'tournament_id' => $category->tournament_id,
            'category_id' => $category->id,
            'local_couple_id' => null,
            'visiting_couple_id' => null,
            'main_draw' => 1,
            'round' => 1,
            'order' => 0
        ];
        $match = TournamentMatch::create($item);
        $viablesSchedules[$indexSchedulesFinal]->update(['tournament_match_id' => $match->id]);

    }

    public static function assignCouplesPlayoff( Category $category){

        $couplesGroupA = array();
        $couplesGroupB = array();

        $couplesGroupA = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->where('couples.league_number', 1)
            ->leftJoin("tournament_couples_clasifications","couples.id", "=", "tournament_couples_clasifications.couple_id")
            ->where('tournament_couples_clasifications.deleted_at', NULL)
            ->where('couples.category_id', $category->id)
            ->select('couples.id')
            ->orderBy('tournament_couples_clasifications.total_points', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_won', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_lost', 'asc')
            ->orderBy('tournament_couples_clasifications.games_avg', 'desc')
            ->orderBy('couples.name', 'asc')
            ->limit(2)->get();

        $couplesGroupB = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->where('couples.league_number', 2)
            ->leftJoin("tournament_couples_clasifications","couples.id", "=", "tournament_couples_clasifications.couple_id")
            ->where('tournament_couples_clasifications.deleted_at', NULL)
            ->where('couples.category_id', $category->id)
            ->select('couples.id')
            ->orderBy('tournament_couples_clasifications.total_points', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_won', 'desc')
            ->orderBy('tournament_couples_clasifications.sets_lost', 'asc')
            ->orderBy('tournament_couples_clasifications.games_avg', 'desc')
            ->orderBy('couples.name', 'asc')
            ->limit(2)->get();

        // Assign PlayOff
        // Semi-final 1
        TournamentMatch::where('category_id', $category->id)
            ->where('tournament_id', $category->tournament_id)
            ->where('main_draw', 1)
            ->where('round', 0)
            ->where('order', 0)
            ->whereNull('league_number')
            ->update(['local_couple_id' => $couplesGroupA[0]->id, 'visiting_couple_id' => $couplesGroupB[1]->id]);
        
        // Semi-final 2
        TournamentMatch::where('category_id', $category->id)
            ->where('tournament_id', $category->tournament_id)
            ->where('main_draw', 1)
            ->where('round', 0)
            ->where('order', 1)
            ->whereNull('league_number')
            ->update(['local_couple_id' => $couplesGroupB[0]->id, 'visiting_couple_id' => $couplesGroupA[1]->id]);
        
          
    }


    public static function getResultMatch( $tournamentMatchId, $isPickleball = false ){
        
        $nameFunction = 'checkResultSetCorrect';
        $funcion2Name = 'setWinner';
        if( $isPickleball == true ){
            $nameFunction = 'checkResultSetCorrectPickleball';
            $funcion2Name = 'setWinnerPickeball';
        }

        $match = TournamentMatch::findOrFail( $tournamentMatchId );
        $setWinNumberLocal = 0;
        $setWinNumberVisiting = 0;

        if( $nameFunction($match->result_set_1)){
            $setResult1 = explode('-',$match->result_set_1);
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_1);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $localScores[] = [
                'mainScore' => $setResult1[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult1[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        if( $nameFunction($match->result_set_2)){
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_2);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $setResult2 = explode('-',$match->result_set_2);
            $localScores[] = [
                'mainScore' => $setResult2[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult2[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        if( $nameFunction($match->result_set_3)){
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_3);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $setResult3 = explode('-',$match->result_set_3);
            $localScores[] = [
                'mainScore' => $setResult3[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult3[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        $localWinner = false;
        $visitingWinner = false;
        if($setWinNumberLocal >= 2){
            $localWinner = true;
        }
        if($setWinNumberVisiting >= 2){
            $visitingWinner = true;
        }
        return array($localScores, $visitingScores, $localWinner, $visitingWinner);

    }


    public static function getResultPickleballMatch($tournamentMatchId ){
        
        $functionName = 'checkResultSetCorrectPickleball';
        $funcion2Name = 'setWinnerPickeball';
        $match = TournamentMatch::findOrFail( $tournamentMatchId );
        $setWinNumberLocal = 0;
        $setWinNumberVisiting = 0;

        if( $functionName($match->result_set_1)){
            $setResult1 = explode('-',$match->result_set_1);
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_1);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $localScores[] = [
                'mainScore' => $setResult1[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult1[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        if( $functionName($match->result_set_2)){
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_2);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $setResult2 = explode('-',$match->result_set_2);
            $localScores[] = [
                'mainScore' => $setResult2[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult2[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        if( $functionName($match->result_set_3)){
            list($localWinner, $visitingWinner) = self::$funcion2Name($match->result_set_3);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
            $setResult3 = explode('-',$match->result_set_3);
            $localScores[] = [
                'mainScore' => $setResult3[0],
                'isWinner' => $localWinner
            ];
            $visitingScores[] = [
                'mainScore' => $setResult3[1],
                'isWinner' => $visitingWinner
            ];
        }else{
            $localScores[] = [
                'mainScore' =>''
            ];
            $visitingScores[] = [
                'mainScore' =>''
            ];
        }
        $localWinner = false;
        $visitingWinner = false;
        if($setWinNumberLocal >= 2){
            $localWinner = true;
        }
        if($setWinNumberVisiting >= 2){
            $visitingWinner = true;
        }
        return array($localScores, $visitingScores, $localWinner, $visitingWinner);

    }

    public static function setWinner($resultSet){
        
        if( checkResultSetCorrect($resultSet)){
            $setResult = explode('-', $resultSet);
            if( $setResult[0] == 7){
                return array(true, false);
            }elseif( $setResult[0] == 6 && $setResult[1] <= 4){
                return array(true, false);
            }else{
                return array(false, true);
            }
        }else{
            return array(false, false);
        }
    }

    public function getMatchData( string $id){

        $match = TournamentMatch::findOrFail($id);
        $category = Category::findOrFail($match->category_id);

        $tournament = Tournament::where('id', $category->tournament_id)->first();
        $this->authorize('view', $tournament);


        if($match){
            if( $match->back_draw == 1){
                $maxRound = TournamentMatch::where('category_id', '=', $match->category_id)->where('back_draw', 1)->max('round'); // ACHTUM, I HAVE CHANGED THE QUERY (TOURNAMENT_ID)
            }else{
                $maxRound = TournamentMatch::where('category_id', '=', $match->category_id)->where('main_draw', 1)->max('round'); // ACHTUM, I HAVE CHANGED THE QUERY (TOURNAMENT_ID)
            }
        }
        
        $returnTo = 'main-draw';
        $typeDraw = 1;
        if($category->type == 3 || $category->type == 4){
            if($match->back_draw == 1){
                $typeDraw = 2;
                $returnTo = 'back-draw';
            }elseif($match->back_draw){
                $typeDraw = 1;
                $returnTo = 'main-draw';
            }
        }elseif($category->type == 1 || $category->type == 2 || $category->type == 6){ // Normal League
            $returnTo = 'normal-league-draw';
        }

        $matchInfo = TournamentMatchDateCourt::where('tournament_match_id', $match->id)->first();
       
        
        $recommendedTimetables = TournamentMatchDateCourt::where('tournament_id', $match->tournament_id)
            ->whereNull('tournament_match_id');
            if($category->type == 3 || $category->type == 4){
                $dateLastMatchsPreviousRound = self::getLastMatchsPreviousRound($id, $typeDraw );
                if( $dateLastMatchsPreviousRound ){
                    $recommendedTimetables->where('date', '>', self::getLastMatchsPreviousRound($id, $typeDraw ));
                }
            }elseif($category->type == 2){
              //  $recommendedTimetables->where('date', '>', self::getLaterMatchLeague($id));
            }
            else{
                $recommendedTimetables->where('date', '>', self::getLaterDateRound($id));
            }
        $recommendedTimetables =  $recommendedTimetables->orderBy('date', 'asc')->get();

  

        if($match->match_finished == 1){
            $recommendedTimetables = false;
        }
        return response()->json([
            'message' => 200,
            'max_round' => $maxRound,
            'category_id' => $match->category_id,
            'category_type' => $category->type,
            'draw_type' => $returnTo,
            'tournament_id' => $match->tournament_id,
            'sport_id' => $tournament->sport_type,
            'match_type' => $category->match_type,
            'match_finished' => $match->match_finished,
            'round' => $match->round,
            'start_tournament' => $match->tournament->start_date,
            'end_tournament' => $match->tournament->end_date,
            'result_set_1' => $match->result_set_1,
            'result_set_2' => $match->result_set_2,
            'result_set_3' => $match->result_set_3,
            'date' => $matchInfo ? $matchInfo->date : '',
            'court' => $matchInfo ? $matchInfo->court_id : '',
            'recommended_timetables' => $recommendedTimetables ? $recommendedTimetables->map(function($timetable){
                $dateArray = explode(' ', $timetable->date);
                $hourArray = explode(':', $dateArray[1]);
                return [
                    "id" => $timetable->id,
                    "date" => $dateArray[0],
                    "time" => $hourArray[0].':'.$hourArray[1],
                    "day" => Carbon::parse($timetable->date)->format('N'),
                    "court" => $timetable->court->name
                ];
            }) : [],
            "local_players" => $match->local_couple ? $match->local_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name.' '.$player->user->surname
                        ];
                    }) : [],
            "visiting_players" => $match->visiting_couple ? $match->visiting_couple->players->map(function($player){
                return [
                    "id" => $player->user->id,
                    "name" => $player->user->name.' '.$player->user->surname
                ];
            }): [], 
        ]);

    }

    
    public function configMatchesPage(string $tournamentId)
    {
        $courts = Court::where('club_id', auth("api")->user()->club_id)
                           ->get();
        $tournament = Tournament::findOrFail($tournamentId);

        $this->authorize('view', $tournament);
        
        $tournament['category'] = $tournament->categories->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,     
            ];
        });
        $tournament['schedule'] = $tournament->schedule;
        $tournament['categories'] = null;
        
        
        return response()->json( [
            'courts' => $courts,
            'tournament_start' => $tournament->start_date,
            'tournament_end' => $tournament->end_date,
            'tournament_categories' => $tournament['category']
        ]);
    }


    public function getAllMatches( Request $request){
        
        $tournamentId = $request->tournament_id;

        $tournament = Tournament::findOrFail($tournamentId);
        $this->authorize('view', $tournament);

        $playerName = $request->player_name_search;
        $statusMatchId = $request->status_match_id;
        $courtId = $request->court_id;
        $date = $request->date;
        $categoryId = $request->category_id;

        $matchesTournament = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.tournament_id', $tournamentId)
            ->join('tournament_matches_date_court', 'tournament_matches.id', '=', 'tournament_matches_date_court.tournament_match_id')
            ->join('categories', 'categories.id', '=', 'tournament_matches.category_id')
            ->join('courts', 'courts.id', '=', 'tournament_matches_date_court.court_id')
            ->where('tournament_matches_date_court.deleted_at', NULL);
            if( $statusMatchId == '0' || $statusMatchId == '1' ){
                $matchesTournament->where('tournament_matches.match_finished', $statusMatchId);
            }
            if( $courtId ){
                $matchesTournament->where('tournament_matches_date_court.court_id', $courtId);
            }
            if( $categoryId ){
                $matchesTournament->where('tournament_matches.category_id', $categoryId);
            }
            if( $date ){
                $dateFormat = Carbon::parse($date)->format("Y-m-d");
                $matchesTournament->whereDate("tournament_matches_date_court.date", $dateFormat);
            }
            $matchesTournament = $matchesTournament->select('tournament_matches.id',
                'tournament_matches.round',
                'tournament_matches.local_couple_id',
                'tournament_matches.visiting_couple_id',
                'tournament_matches.result_set_1',
                'tournament_matches.result_set_2',
                'tournament_matches.result_set_3',
                'tournament_matches_date_court.date',
                'tournament_matches.league_number',
                'tournament_matches.match_finished',
                'categories.name as category_name',
                'courts.name as court_name')
            ->orderBy('tournament_matches_date_court.date', 'asc')
            ->paginate(20);


        $matches = array();

        //$maxRound = TournamentMatch::where('category_id', '=', $id)->max('round');
        
        //$matches =  $matchesTournament->map(function($item) {
        foreach ($matchesTournament as $item ) {
            
            $result = '';
            if(is_null($item->result_set_1) == false){
                $result .= $item->result_set_1;
            }
            if(is_null($item->result_set_2) == false){
                $result .= '/'.$item->result_set_2;
            }
            if(is_null($item->result_set_3) == false){
                $result .= '/'.$item->result_set_3;
            }
            $localCouple = $item->local_couple_id != null ? Couple::findOrFail($item->local_couple_id) : false;
            $namesLocalCouple = '';
            if( $localCouple != false ){
                $localCouple->players->map(function($player) use(&$namesLocalCouple){
                    if(strlen($player->user->name) > 15){
                        $namesLocalCouple .= substr($player->user->name, 0, 15).'.'.' - ';
                    }else{
                        $namesLocalCouple .= $player->user->name.' - '; 
                    }
                });
            }
            if(strlen($namesLocalCouple) > 0){
                $namesLocalCouple = substr($namesLocalCouple, 0, -3);
            }

            $visitingCouple = $item->visiting_couple_id != null ? Couple::findOrFail($item->visiting_couple_id): false;
            $namesVisitingCouple = '';
            if( $visitingCouple != false ){
                $visitingCouple->players->map(function($player) use(&$namesVisitingCouple){
                    if(strlen($player->user->name) > 15){
                        $namesVisitingCouple .= substr($player->user->name, 0, 15).'.'.' - ';
                    }else{
                        $namesVisitingCouple .= $player->user->name.' - '; 
                    }
                    });
            }
            if(strlen($namesLocalCouple) > 0){
                $namesLocalCouple = substr($namesLocalCouple, 0, -3);
            }
            if(strlen($namesVisitingCouple) > 0){
                $namesVisitingCouple = substr($namesVisitingCouple, 0, -3);
            }
            
            if( $item->match_finished == '1'){
                if( $tournament->sport_type == 1 || $tournament->sport_type == 2){
                    list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id);
                }else{
                    list($scoresLocal, $scoresVisiting, $localWinner, $visitingWinner) = self::getResultMatch($item->id, true);
                }
            }else{
                $localWinner = false;
                $visitingWinner = false;
            }
            /*
                player_code: 1 -> exist name; 2->rival to be determined;
            */
            $matches[] = [
                'id' => $item->id,
                'journey' => $item->round,
                'local_winner' => $localWinner,
                'local_player_code' => $localCouple ? '1' : '2',
                'names_local_couple' => $namesLocalCouple,
                'visiting_winner' => $visitingWinner,
                'visiting_player_code' => $visitingCouple ? '1' : '2',
                'names_visiting_couple' => $namesVisitingCouple,
                'result' => $result,
                'category_name' => $item->category_name,
                'court_name' => $item->court_name,
                'time' =>  $item->date ? Carbon::parse($item->date)->format('N H:i\h jS \of F') : '',
                'league_number' => $item->league_number,
                'match_status' => $item->match_finished
            ];
        };

        $total = $matchesTournament->total();
        if($playerName){
            $arrayFilter = array_filter($matches, function($item) use($playerName){
                return str_contains(strtolower($item['names_local_couple']), $playerName) || str_contains(strtolower($item['names_visiting_couple']), $playerName);
            });
            $matches = [];
            foreach ($arrayFilter as $key => $value) {
                $matches[] = $value;
            }
            $total = count($matches);
        }

        


        return response()->json([
            'message' => 200,
            'matches' => $matches,
            'total' => $total
        ]);


    }


    public function getAllPlayers( Request $request){
        
        $tournamentId = $request->tournament_id;

        $tournament = Tournament::findOrFail($tournamentId);
        $this->authorize('view', $tournament);

        $playerName = $request->player_name_search;
        $status = $request->status_match_id;
        $categoryId = $request->category_id;

        $payerTournament = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->where('couples.tournament_id', $tournamentId)
            ->join('couple_players', 'couple_players.couple_id', '=', 'couples.id')
            ->where('couple_players.deleted_at', NULL)
            ->join('categories', 'categories.id', '=', 'couples.category_id')
            ->where('categories.deleted_at', NULL)
            ->join('users', 'couple_players.user_id', '=', 'users.id')
            ->join('club_users', 'club_users.user_id', '=', 'users.id')
            ->where('club_users.deleted_at', NULL)
            ->where('club_users.status', 'ACCEPT');

            if( $playerName && $playerName != '' ){
                $payerTournament->where(DB::raw("CONCAT(club_users.name, ' ',club_users.surname)") , 'like', '%'.$playerName.'%');
            }

            if( $status == 'PENDING' || $status == 'PAID' ){
                $payerTournament->where('couple_players.paid_status', $status);
            }
           
            if( $categoryId ){
                $payerTournament->where('couples.category_id', $categoryId);
            }
            
            $payerTournament = $payerTournament->select(
                'couple_players.id',
                'categories.name',
                'club_users.name as player_name',
                'club_users.surname as player_surname',
                'couple_players.paid_status')
            ->orderBy('club_users.name', 'asc')
            ->paginate(10);
        


        return response()->json([
            'message' => 200,
            'players' => $payerTournament,
            'total' => $payerTournament->total(),
            'categories' => $tournament->categories
        ]);
    }

    public function paidPlayerTournament(string $couplePlayerId){

        $user = auth("api")->user();
        $couplePlayer = CouplePlayer::findOrFail($couplePlayerId);

        if($couplePlayer != false){
            $couplePlayer->update(['paid_status' => 'PAID']);
        }
    
        return response()->json([
            'message' => 200
        ]);
    }


    public function unpaidPlayerTournament(string $couplePlayerId){

        $user = auth("api")->user();
        $couplePlayer = CouplePlayer::findOrFail($couplePlayerId);

        if($couplePlayer != false){
            $couplePlayer->update(['paid_status' => 'PENDING']);
        }
    
        return response()->json([
            'message' => 200
        ]);
    }

    public function saveResultPickleball(string $id, Request $request)
    {
        $match = TournamentMatch::findOrFail($id);
        $category = Category::findOrFail($match->category_id);

        $tournament = Tournament::findOrFail($category->tournament_id);
        $this->authorize('update', $tournament);
        
        $errors = [];
        $results = json_decode($request->results, 1);

        $result_set_1 = $results['result_set_1'];
        if( $result_set_1 != null && $result_set_1 != '' && self::checkIfCorrectResultSetPickleball($result_set_1) == false){
            $errors[] = "El resultado del Set 1  es incorrecto.";
        }

        $result_set_2 = $results['result_set_2'];
        if( $result_set_2 != null && $result_set_2 != '' && self::checkIfCorrectResultSetPickleball($result_set_1) == false){
            $errors[] = "El resultado del Set 2 es incorrecto.";
        }

        $result_set_3 = $results['result_set_3'];
        if( $result_set_3 != null && $result_set_3 != '' && self::checkIfCorrectResultSetPickleball($result_set_3) == false){
            $errors[] = "El resultado del Set 3 es incorrecto.";
        }

        if(count($errors) > 0){
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $setWinNumberLocal = 0;
        $setWinNumberVisiting = 0;

        if( checkResultSetCorrectPickleball($result_set_1)){
            list($localWinner, $visitingWinner) = self::setWinnerPickeball($result_set_1);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        if( checkResultSetCorrectPickleball($result_set_2)){
            list($localWinner, $visitingWinner) = self::setWinnerPickeball($result_set_2);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        if( checkResultSetCorrectPickleball($result_set_3)){
            list($localWinner, $visitingWinner) = self::setWinnerPickeball($result_set_3);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        $localWinner = false;
        $visitingWinner = false;
        $matchFinished = 0;
        $coupleWinner = 0;
        $coupleLosser = 0;
        if($setWinNumberLocal >= 2){
            $localWinner = true;
            $matchFinished = 1;
            $coupleWinner = $match->local_couple_id != null ? $match->local_couple_id : null;
            $coupleLosser = $match->visiting_couple_id!= null ? $match->visiting_couple_id : null;
        }
        if($setWinNumberVisiting >= 2){
            $visitingWinner = true;
            $matchFinished = 1;
            $coupleWinner = $match->visiting_couple_id!= null ? $match->visiting_couple_id : null;
            $coupleLosser = $match->local_couple_id != null ? $match->local_couple_id : null;
        }

        if( $matchFinished == 1 ){
            if( $coupleLosser!= null){
                $losserCouple = Couple::findOrFail($coupleLosser);
                if( $losserCouple->matches_played == 0){
                    $losserCouple->update(['to_back_draw' => 1]);
                }
            }
            if( $match->local_couple_id != null){
                DB::table('couples')->where('id', $match->local_couple_id)->increment('matches_played');
            }
            if( $match->visiting_couple_id != null){
                DB::table('couples')->where('id', $match->visiting_couple_id)->increment('matches_played');
            }
        }

        
        $match->update([
            'result_set_1' => $result_set_1,
            'result_set_2' => $result_set_2,
            'result_set_3' => $result_set_3,
            'match_finished' => $matchFinished
        ]);

        $rest = 0;
        if($matchFinished == 1){
           /*if($match->category->type == 3 && $match->main_draw == 1 && self::isFirstMatchCouple($coupleLosser, $match->category_id, $id)){ // Losser go to back draw
                self::goBackDrawCouple($match->category->id, $coupleLosser);
            }*/
            if( is_null($match->league_number)){//$category->type == 3 || $category->type == 4){
                $matchTournament = self::getNextRoundMatch($match);
                $rest = fmod($match->order , 2);
                if($matchTournament){
                    if( $rest == 0){
                        $matchTournament->update(['local_couple_id' => $coupleWinner]);
                    }else{
                        $matchTournament->update(['visiting_couple_id' => $coupleWinner]);
                    }
                }
            }

            // If this match belongs to a league add points for the clasifications
            if( is_null($match->league_number) == false || $match->category->type == 1 || $match->category->type == 6){
                self::calculatingResultPickleball($match, $category);
            }

        }


        //Check if we have to create de backdraw
        $couples0MatchsPlayed = 0;
        $totalMatchBackDrawCreated = 0;
        if( $match->category->type == 3 ){
            $totalMatchBackDrawCreated = TournamentMatch::where('category_id', $match->category_id)->where('back_draw', 1)->count();
            $couples0MatchsPlayed = Couple::where('category_id', $match->category_id)->where('matches_played', 0)->count();
            if( $couples0MatchsPlayed == 0 && $totalMatchBackDrawCreated == 0){ // If every couples has played almost one match and not created backdraw
                self::createDraw($match->category_id, 2);
                self::directNextBackDrawCategory($match->category_id);
                self::assignMatchCoupleWithScheduleByCategory($match->category_id);
                self::scheduleFinalsMatchByCategory($match->category_id, 2);
                self::assignFirstsMatchesScheduleBackDrawByCategory($match->category_id);
                self::assignMatchScheduleDraw($match->category, 2);
            }
        }

        // Check if we have to create the playoff
        if( $match->category->type == 2 ){
            $totalMatchsGroupsNotFinisched = TournamentMatch::where('category_id', $match->category_id) 
                ->whereNotNull('league_number')
                ->where('match_finished', 0)
                ->count();
            if( $totalMatchsGroupsNotFinisched == 0){
                self::assignCouplesPlayoff($category);
            }
        }

        
        return response()->json([
            'message' => 200,
            'rest' => $rest,
            'couples0MatchsPlayed' => $couples0MatchsPlayed,
            'totalMatchBackDrawCreated' => $totalMatchBackDrawCreated,
            'rest' => $rest,
        ]);

       

    }


    public static function setWinnerPickeball($resultSet){
        
        if( true ){//checkResultSetCorrectPickleball($resultSet)){
            $setResult = explode('-', $resultSet);
            if( ($setResult[0] - $setResult[1] >= 2) && $setResult[0] > 10 ){
                return array(true, false);
            }elseif(($setResult[1] - $setResult[0] >= 2) && $setResult[1] > 10){
                return array(false, true);
            }else{
                return array(false, false);
            }
        }else{
            return array(false, false);
        }
    }


    public function checkIfCorrectResultSetPickleball(string $set)
    {
    
        $pattern = '/^(0|[1-9]\d?)-^(0|[1-9]\d?)$/';
        if (preg_match($pattern, $set)){
            return false;
        }
        return true; 

        $result = explode('-', $set);
        $localResult = intval($result[0]);
        $visitingResult = intval($result[1]);
    

        $possibleNumbers = [11, 12, 13, 14, 15, 20, 21];
        if( !in_array($localResult, $possibleNumbers) || in_array($visitingResult, $possibleNumbers)){
            return false;
        }
        
        
        return abs($localResult - $visitingResult) >= 2;
    }

    public function saveResult( string $id, Request $request){

        $match = TournamentMatch::findOrFail($id);
        $category = Category::findOrFail($match->category_id);

        $tournament = Tournament::findOrFail($category->tournament_id);
        $this->authorize('update', $tournament);

        $errors = [];

        $pattern = '/^(6|7)-[0-7]$|^[0-7]-(6|7)$/';
        $results = json_decode($request->results, 1);

        $result_set_1 = $results['result_set_1'];
        if( ($result_set_1 != null && $result_set_1 != '' && strlen($result_set_1) == 3) && preg_match($pattern, $result_set_1) == false){
            $errors[] = "El resultado del Set 1  es incorrecto.";
        }

        $result_set_2 = $results['result_set_2'];
        if( ($result_set_2 != null && $result_set_2 != '' && strlen($result_set_2) == 3) && preg_match($pattern, $result_set_2) == false){
            $errors[] = "El resultado del Set 2 es incorrecto.";
        }

        $result_set_3 = $results['result_set_3'];
        if( ($result_set_3 != null && $result_set_3 != '' && strlen($result_set_3) == 3) && preg_match($pattern, $result_set_3) == false){
            $errors[] = "El resultado del Set 3 es incorrecto.";
        }

        if(count($errors) > 0){
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $setWinNumberLocal = 0;
        $setWinNumberVisiting = 0;

        if( checkResultSetCorrect($result_set_1)){
            list($localWinner, $visitingWinner) = self::setWinner($result_set_1);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        if( checkResultSetCorrect($result_set_2)){
            list($localWinner, $visitingWinner) = self::setWinner($result_set_2);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        if( checkResultSetCorrect($result_set_3)){
            list($localWinner, $visitingWinner) = self::setWinner($result_set_3);
            if( $localWinner == true){
                $setWinNumberLocal ++;
            }
            if( $visitingWinner == true){
                $setWinNumberVisiting ++;
            }
        }

        $localWinner = false;
        $visitingWinner = false;
        $matchFinished = 0;
        $coupleWinner = 0;
        $coupleLosser = 0;
        if($setWinNumberLocal >= 2){
            $localWinner = true;
            $matchFinished = 1;
            $coupleWinner = $match->local_couple_id != null ? $match->local_couple_id : null;
            $coupleLosser = $match->visiting_couple_id!= null ? $match->visiting_couple_id : null;
        }
        if($setWinNumberVisiting >= 2){
            $visitingWinner = true;
            $matchFinished = 1;
            $coupleWinner = $match->visiting_couple_id!= null ? $match->visiting_couple_id : null;
            $coupleLosser = $match->local_couple_id != null ? $match->local_couple_id : null;
        }

        if( $matchFinished == 1 ){
            if( $coupleLosser!= null){
                $losserCouple = Couple::findOrFail($coupleLosser);
                if( $losserCouple->matches_played == 0){
                    $losserCouple->update(['to_back_draw' => 1]);
                }
            }
            if( $match->local_couple_id != null){
                DB::table('couples')->where('id', $match->local_couple_id)->increment('matches_played');
            }
            if( $match->visiting_couple_id != null){
                DB::table('couples')->where('id', $match->visiting_couple_id)->increment('matches_played');
            }
        }

        
        $match->update([
            'result_set_1' => $result_set_1,
            'result_set_2' => $result_set_2,
            'result_set_3' => $result_set_3,
            'match_finished' => $matchFinished
        ]);

        $rest = 0;
        if($matchFinished == 1){
           /*if($match->category->type == 3 && $match->main_draw == 1 && self::isFirstMatchCouple($coupleLosser, $match->category_id, $id)){ // Losser go to back draw
                self::goBackDrawCouple($match->category->id, $coupleLosser);
            }*/
            if( is_null($match->league_number)){//$category->type == 3 || $category->type == 4){
                $matchTournament = self::getNextRoundMatch($match);
                $rest = fmod($match->order , 2);
                if($matchTournament){
                    if( $rest == 0){
                        $matchTournament->update(['local_couple_id' => $coupleWinner]);
                    }else{
                        $matchTournament->update(['visiting_couple_id' => $coupleWinner]);
                    }
                }
            }

            // If this match belongs to a league add points for the clasifications
            if( is_null($match->league_number) == false || $match->category->type == 1 || $match->category->type == 6){
                self::calculatingResult($match, $category);
            }

        }


        //Check if we have to create de backdraw
        $couples0MatchsPlayed = 0;
        $totalMatchBackDrawCreated = 0;
        if( $match->category->type == 3 ){
            $totalMatchBackDrawCreated = TournamentMatch::where('category_id', $match->category_id)->where('back_draw', 1)->count();
            $couples0MatchsPlayed = Couple::where('category_id', $match->category_id)->where('matches_played', 0)->count();
            if( $couples0MatchsPlayed == 0 && $totalMatchBackDrawCreated == 0){ // If every couples has played almost one match and not created backdraw
                self::createDraw($match->category_id, 2);
                self::directNextBackDrawCategory($match->category_id);
                self::assignMatchCoupleWithScheduleByCategory($match->category_id);
                self::scheduleFinalsMatchByCategory($match->category_id, 2);
                self::assignFirstsMatchesScheduleBackDrawByCategory($match->category_id);
                self::assignMatchScheduleDraw($match->category, 2);
            }
        }

        // Check if we have to create the playoff
        if( $match->category->type == 2 ){
            $totalMatchsGroupsNotFinisched = TournamentMatch::where('category_id', $match->category_id) 
                ->whereNotNull('league_number')
                ->where('match_finished', 0)
                ->count();
            if( $totalMatchsGroupsNotFinisched == 0){
                self::assignCouplesPlayoff($category);
            }
        }

        
        return response()->json([
            'message' => 200,
            'rest' => $rest,
            'couples0MatchsPlayed' => $couples0MatchsPlayed,
            'totalMatchBackDrawCreated' => $totalMatchBackDrawCreated,
            'rest' => $rest,
        ]);
    }


    public function calculatingResult( TournamentMatch $match, Category $category){
        
        $couple1Id = $match->local_couple_id;
        $couple2Id = $match->visiting_couple_id;
        
        $pointsPerWin = 3;// $category->points_per_win;
        $pointLosserByWinSet = 1;// $category->points_per_set_losser;

        self::calculatingResultTeam($couple1Id, $pointsPerWin, $pointLosserByWinSet );
        self::calculatingResultTeam($couple2Id, $pointsPerWin, $pointLosserByWinSet );

    }


    public function calculatingResultTeam( $coupleId, $pointsPerWin, $pointLosserByWinSet  ){
        
        $matchsWin = 0;
        $matchsLost = 0;
        $setsWin = 0;
        $setsLost = 0;
        $gamesWin = 0;
        $gamesLost = 0;
        $totalPoints = 0;
        $matchesPlayed = 0;

        $matchs = TournamentMatch::whereNotNull('result_set_1')
            ->where('local_couple_id', $coupleId)->get();
        
        foreach ($matchs as $match) {
            $setsWonCurrentMatch = 0;
            $setsLostCurrentMatch = 0;
            // Calculate games wins
            if( self::checkResultSetCorrect($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                $gamesWin += (int)$setResult1[0];
                $gamesLost += (int)$setResult1[1];
            }
            if( self::checkResultSetCorrect($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                $gamesWin += (int)$setResult2[0];
                $gamesLost += (int)$setResult2[1];
            }
            if( self::checkResultSetCorrect($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                $gamesWin += (int)$setResult3[0];
                $gamesLost += (int)$setResult3[1];
            }

            // Calculate sets wins
            if( self::checkResultSetCorrect($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                if( (int)$setResult1[0] == 7 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif((int)$setResult1[1] == 7 ){
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }elseif( (int)$setResult1[0] == 6){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }else{
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }
            }
            if( self::checkResultSetCorrect($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                if( (int)$setResult2[0] == 7 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif((int)$setResult2[1] == 7 ){
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }elseif( (int)$setResult2[0] == 6){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }else{
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }
            }
            if( self::checkResultSetCorrect($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                if( (int)$setResult3[0] == 7 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif((int)$setResult3[1] == 7 ){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }elseif( (int)$setResult3[0] == 6){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }else{
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }
            }

            //Calculate points
            if( $setsWonCurrentMatch == 2){
                $totalPoints += (int)$pointsPerWin;
                $matchsWin += 1;
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosserByWinSet;
                }
                $matchsLost += 1;
            }/*else{
                $totalPoints = 0;
                $matchsLost = 0;
            }*/
            $matchesPlayed ++;
        }

        $matchs = TournamentMatch::whereNotNull('result_set_1')
                ->where('visiting_couple_id', $coupleId)->get();
        foreach ($matchs as $match) {
            $setsWonCurrentMatch = 0;
            $setsLostCurrentMatch = 0;
            // Calculate games wins
            if( self::checkResultSetCorrect($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                $gamesWin += (int)$setResult1[1];
                $gamesLost += (int)$setResult1[0];
            }
            if( self::checkResultSetCorrect($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                $gamesWin += (int)$setResult2[1];
                $gamesLost += (int)$setResult2[0];
            }
            if( self::checkResultSetCorrect($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                $gamesWin += (int)$setResult3[1];
                $gamesLost += (int)$setResult3[0];
            }

            // Calculate sets wins
            if( self::checkResultSetCorrect($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                if( (int)$setResult1[1] == 7 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif((int)$setResult1[0] == 7 ){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }elseif( (int)$setResult1[1] == 6){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }else{
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }
            }
            if( self::checkResultSetCorrect($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                if( (int)$setResult2[1] == 7 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif((int)$setResult2[0] == 7 ){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }elseif( (int)$setResult2[1] == 6){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }else{
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }
            }
            if( self::checkResultSetCorrect($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                if( (int)$setResult3[1] == 7 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif((int)$setResult3[0] == 7 ){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }elseif( (int)$setResult3[1] == 6){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }else{
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }
            }

            //Calculate points
            if( $setsWonCurrentMatch == 2){
                $totalPoints += (int)$pointsPerWin;
                $matchsWin += 1;
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosserByWinSet;
                }
                $matchsLost += 1;
            }
            $matchesPlayed ++;
        }

        // Save new results
        $coupleResult = TournamentCouplesClasification::where('couple_id', $coupleId)->first();
        if( $coupleResult ){
            $coupleResult->delete();
        }

        TournamentCouplesClasification::create([
            'tournament_id' => $match->tournament_id,
            'category_id' => $match->category_id,
            'couple_id' => $coupleId,
            'total_points' => $totalPoints,
            'matches_played' => $matchesPlayed,
            'matchs_won' => $matchsWin,
            'matchs_lost' => $matchsLost,
            'games_won' => $gamesWin,
            'games_lost' => $gamesLost,
            'games_avg' => $gamesWin - $gamesLost,
            'sets_won' => $setsWin,
            'sets_lost' => $setsLost,
        ]);
        
    }

    public function calculatingResultPickleball( TournamentMatch $match, Category $category){
        
        $couple1Id = $match->local_couple_id;
        $couple2Id = $match->visiting_couple_id;
        
        $pointsPerWin = 3;// $category->points_per_win;
        $pointLosserByWinSet = 1;// $category->points_per_set_losser;

        $pointsPerWin_2_0 = $category->points_per_win_2_0;
        $pointsPerWin_2_1 = $category->points_per_win_2_1;
        $pointLosser_0_2 = $category->points_per_lost_0_2;
        $pointLosser_1_2 = $category->points_per_lost_1_2;

        self::calculatingResultTeamPickleball($couple1Id, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2 );
        self::calculatingResultTeamPickleball($couple2Id, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2 );

    }


    public function calculatingResultTeamPickleball( $coupleId, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2  ){
        
        $matchsWin = 0;
        $matchsLost = 0;
        $setsWin = 0;
        $setsLost = 0;
        $gamesWin = 0;
        $gamesLost = 0;
        $totalPoints = 0;
        $matchesPlayed = 0;

        $matchs = TournamentMatch::whereNotNull('result_set_1')
            ->where('local_couple_id', $coupleId)->get();
        
        foreach ($matchs as $match) {
            $setsWonCurrentMatch = 0;
            $setsLostCurrentMatch = 0;
            // Calculate games wins
            if( checkResultSetCorrectPickleball($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                $gamesWin += (int)$setResult1[0];
                $gamesLost += (int)$setResult1[1];
            }
            if( checkResultSetCorrectPickleball($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                $gamesWin += (int)$setResult2[0];
                $gamesLost += (int)$setResult2[1];
            }
            if( checkResultSetCorrectPickleball($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                $gamesWin += (int)$setResult3[0];
                $gamesLost += (int)$setResult3[1];
            }

            // Calculate sets wins
            if( checkResultSetCorrectPickleball($match->result_set_1)){
                $setResult_1 = explode('-', $match->result_set_1);
                if( ($setResult_1[0] - $setResult_1[1] >= 2) && $setResult_1[0] > 10 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif(($setResult_1[1] - $setResult_1[0] >= 2) && $setResult_1[1] > 10){
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }  
            }
            if( checkResultSetCorrectPickleball($match->result_set_2)){
                $setResult_2 = explode('-', $match->result_set_2);
                if( ($setResult_2[0] - $setResult_2[1] >= 2) && $setResult_2[0] > 10 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif(($setResult_2[1] - $setResult_2[0] >= 2) && $setResult_2[1] > 10){
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }  
            }
            if( checkResultSetCorrectPickleball($match->result_set_3)){
                $setResult_3 = explode('-', $match->result_set_3);
                if( ($setResult_3[0] - $setResult_3[1] >= 2) && $setResult_3[0] > 10 ){
                    $setsWonCurrentMatch ++;
                    $setsWin += 1;
                }elseif(($setResult_3[1] - $setResult_3[0] >= 2) && $setResult_3[1] > 10){
                    $setsLostCurrentMatch ++;
                    $setsLost += 1;
                }  
            }

            //Calculate points
            /*if( $setsWonCurrentMatch == 2){
                $totalPoints += (int)$pointsPerWin;
                $matchsWin += 1;
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosserByWinSet;
                }
                $matchsLost += 1;
            }
            $matchesPlayed ++;*/


            //Calculate points
            if( $setsWonCurrentMatch == 2){
                if($setsLostCurrentMatch == 1 ){
                    $totalPoints += (int)$pointsPerWin_2_1;
                }else{
                    $totalPoints += (int)$pointsPerWin_2_0;
                }
                $matchsWin += 1;
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosser_1_2;
                }else{
                    $totalPoints += (int)$pointLosser_0_2;
                }
                $matchsLost += 1;
            }
            $matchesPlayed ++;





        }

        $matchs = TournamentMatch::whereNotNull('result_set_1')
                ->where('visiting_couple_id', $coupleId)->get();
        foreach ($matchs as $match) {
            $setsWonCurrentMatch = 0;
            $setsLostCurrentMatch = 0;
            // Calculate games wins
            if( checkResultSetCorrectPickleball($match->result_set_1)){
                $setResult1 = explode('-',$match->result_set_1);
                $gamesWin += (int)$setResult1[1];
                $gamesLost += (int)$setResult1[0];
            }
            if( checkResultSetCorrectPickleball($match->result_set_2)){
                $setResult2 = explode('-',$match->result_set_2);
                $gamesWin += (int)$setResult2[1];
                $gamesLost += (int)$setResult2[0];
            }
            if( checkResultSetCorrectPickleball($match->result_set_3)){
                $setResult3 = explode('-',$match->result_set_3);
                $gamesWin += (int)$setResult3[1];
                $gamesLost += (int)$setResult3[0];
            }

            // Calculate sets wins
            if( checkResultSetCorrectPickleball($match->result_set_1)){

                $setResult_1 = explode('-', $match->result_set_1);
                if( ($setResult_1[1] - $setResult_1[0] >= 2) && $setResult_1[1] > 10 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif(($setResult_1[0] - $setResult_1[1] >= 2) && $setResult_1[0] > 10){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }  

            }
            if( checkResultSetCorrectPickleball($match->result_set_2)){
                $setResult_2 = explode('-', $match->result_set_2);
                if( ($setResult_2[1] - $setResult_2[0] >= 2) && $setResult_2[1] > 10 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif(($setResult_2[0] - $setResult_2[1] >= 2) && $setResult_2[0] > 10){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }  

            }
            if( checkResultSetCorrectPickleball($match->result_set_3)){
                $setResult_3 = explode('-', $match->result_set_3);
                if( ($setResult_3[1] - $setResult_3[0] >= 2) && $setResult_3[1] > 10 ){
                    $setsWin += 1;
                    $setsWonCurrentMatch ++;
                }elseif(($setResult_3[0] - $setResult_3[1] >= 2) && $setResult_3[0] > 10){
                    $setsLost += 1;
                    $setsLostCurrentMatch ++;
                }  

            }

            //Calculate points
            /*if( $setsWonCurrentMatch == 2){
                $totalPoints += (int)$pointsPerWin;
                $matchsWin += 1;
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosserByWinSet;
                }
                $matchsLost += 1;
            }
            $matchesPlayed ++;*/
            if( $setsWonCurrentMatch == 2){
                if($setsLostCurrentMatch == 1 ){
                    $totalPoints += (int)$pointsPerWin_2_1;
                }else{
                    $totalPoints += (int)$pointsPerWin_2_0;
                }
                $matchsWin += 1;
                $match->update(['match_finished' => '1']);
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosser_1_2;
                }else{
                    $totalPoints += (int)$pointLosser_0_2;
                }
                $matchsLost += 1;
                $match->update(['match_finished' => '1']);
            }else{
                $match->update(['match_finished' => '0']);
            }
            $matchesPlayed ++;
        }

        // Save new results
        $coupleResult = TournamentCouplesClasification::where('couple_id', $coupleId)->first();
        if( $coupleResult ){
            $coupleResult->delete();
        }

        TournamentCouplesClasification::create([
            'tournament_id' => $match->tournament_id,
            'category_id' => $match->category_id,
            'couple_id' => $coupleId,
            'total_points' => $totalPoints,
            'matches_played' => $matchesPlayed,
            'matchs_won' => $matchsWin,
            'matchs_lost' => $matchsLost,
            'games_won' => $gamesWin,
            'games_lost' => $gamesLost,
            'games_avg' => $gamesWin - $gamesLost,
            'sets_won' => $setsWin,
            'sets_lost' => $setsLost,
        ]);
        
    }

    public function checkResultSetCorrect( $result = '' ){
        if( $result != null && $result != '' && strlen($result) == 3 && strpos( $result, '-' ) !== false){
            return true;
        }
        return false;
    }





    public function getNextRoundMatch( TournamentMatch $match){

        $order = intdiv($match->order, 2);
        $typeDraw = $match->main_draw == 1 ? 'main' : 'back';

        if($typeDraw == 'main' ){
            $matchTournament = TournamentMatch::where( 'category_id', '=', $match->category_id)
                ->where('round', '=',  $match->round + 1 )
                ->where('order', '=', $order)
                ->where('league_number', NULL)
                ->where('main_draw', 1)
                ->first();
        }else{
            $matchTournament = TournamentMatch::where( 'category_id', '=', $match->category_id)
                ->where('round', '=', $match->round + 1  )
                ->where('order', '=', $order)
                ->where('league_number', NULL)
                ->where('back_draw', 1)
                ->first();
        }    
        return $matchTournament;            
    }

    /*

    public function goBackDrawCouple( string $categoryId, string $coupleId){
        $tournamentMatchId = DB::SELECT("SELECT id FROM tournament_matches 
            WHERE deleted_at IS NULL AND round = 0 AND back_draw = 1 AND category_id = ?
            AND ( local_couple_id IS NULL OR visiting_couple_id IS NULL) ORDER BY RAND() LIMIT 1"
            , [$categoryId]);

        $tournamentMatch = TournamentMatch::findOrFail($tournamentMatchId[0]->id);
        if( is_null($tournamentMatch->local_couple_id) ){
            $tournamentMatch->update(['local_couple_id' => $coupleId]);
        }else{
            $tournamentMatch->update(['visiting_couple_id' => $coupleId]);
        }

        return response()->json([
            'message' => 200,
            'match' => $tournamentMatch,
            'rest' => $tournamentMatchId[0]->id
        ]);
    }

    */


    /*
    public function isFirstMatchCouple(string $coupleId, string $categoryId, $tournamentMatchId){
        $query = DB::SELECT("SELECT * FROM tournament_matches 
                  WHERE deleted_at IS NULL AND match_finished = 1 AND category_id = ? AND id <> ? AND (local_couple_id = ? OR visiting_couple_id = ? )",
                  [$categoryId, $tournamentMatchId, $coupleId, $coupleId ]);
        if( $query ){
            return false;
        }
        return true;
    }
    
    */


    public function updateScheduleMatch(Request $request){ 

        $match = TournamentMatch::findOrFail( $request->match_id);
        $currentMatchDateCourt = TournamentMatchDateCourt::where('tournament_match_id', $match->id)->first();

        $tournament = Tournament::findOrFail($match->tournament_id);
        $this->authorize('update', $tournament);

        $category = Category::findOrFail($match->category_id);

        $newCurrentMatchDateCourt = TournamentMatchDateCourt::findOrFail($request->new_timetable_id);

        $tournamentMatch = TournamentMatch::findOrFail($request->id);
        $maxRound = TournamentMatch::where('category_id', '=', $tournamentMatch->category_id)->max('round'); 

        $laterMatchsIds = collect([]);
        
        if($category->type == 3 || $category->type == 4 ) { // also change later matchs dates draw and back-draw 

            
            $order = $tournamentMatch->order;
            for ($round = $tournamentMatch->round + 1; $round <= $maxRound; $round++) { 
                $order = intdiv($order, 2);
                $matchTournament = TournamentMatch::where( 'category_id', '=', $tournamentMatch->category_id)
                                    ->where('round', '=', $round)
                                    ->where('order', '=', $order)
                                    ->first();
                $laterMatchsIds->push($matchTournament->id);
            }


            $flag = 0;
            $newDate = $newCurrentMatchDateCourt->date;
            foreach ($laterMatchsIds as $matchId) {
                $nextMatchEarly = TournamentMatchDateCourt::where( 'tournament_match_id', $matchId )->where('date','<=', $newDate)->count();
                if($nextMatchEarly > 0 ){
                    $arrayDate = explode(' ',$newDate);
                    $arrayDay = explode('-', $arrayDate[0]);
                    $arrayHour = explode(':', $arrayDate[1]);
                    $matchDate = Carbon::create($arrayDay[0], $arrayDay[1], $arrayDay[2], $arrayHour[0], $arrayHour[1], 0)->addHours(5);
                    if(self::getLaterDateRound($matchId) > $newDate && $flag != 0){
                        $newDate = self::getLaterDateRound($matchId);
                    }
                    $flag = 1;
                    $recommendedTime = TournamentMatchDateCourt::whereNull('tournament_match_id')
                                    ->where('tournament_id', $tournamentMatch->tournament_id)
                                    ->where('date', '>=', $matchDate)
                                    ->orderBy('date', 'asc')->first();
                    if( $recommendedTime){
                        $newDate = $recommendedTime->date;
                        TournamentMatchDateCourt::where('tournament_match_id', $matchId)->update(['tournament_match_id' => NULL]);
                        $recommendedTime->update(['tournament_match_id' => $matchId]);
                    }
                }
            }
        }

        if($currentMatchDateCourt){
            $currentMatchDateCourt->update(['tournament_match_id' => NULL]);
        }

        if( $newCurrentMatchDateCourt){
            $newCurrentMatchDateCourt->update(['tournament_match_id' => $request->id]);
        }


        return response()->json([
            'message' => 200,
            "ids" => $laterMatchsIds
        ]); 

        $dateArray = explode(' ', $newCurrentMatchDateCourt->date);
        $hourArray = explode(':', $dateArray[1]);

        return response()->json([
            'message' => 200,
            "date" => $dateArray[0],
            "time" => $hourArray[0].':'.$hourArray[1],
            "court" => $newCurrentMatchDateCourt->court->id
        ]);

    }


    public function getLaterDateRound( $matchId ){
        
        $lastTimeRound = '';
        $tournamentMatch = TournamentMatch::findOrFail($matchId);
        
        $orders = array();
        $orders[] = $tournamentMatch->order;
        
        if ($tournamentMatch->order % 2 == 0) {
            $orders[] = $tournamentMatch->order + 1;
        }else {
            $orders[] = $tournamentMatch->order - 1;
        }
        $round = $tournamentMatch->round;

        $lastTimeRound = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->where('tournament_matches.round', '=', $round)
            ->whereIn('tournament_matches.order', $orders)
            ->where( 'tournament_matches.category_id', '=', $tournamentMatch->category_id)
            ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->max('tournament_matches_date_court.date');

        return $lastTimeRound;
        
    }

    public function getLaterMatchLeague( $matchId ){
        
        $lastTimeRound = '';
        $tournamentMatch = TournamentMatch::findOrFail($matchId);
        
        $orders = array();
        $orders[] = $tournamentMatch->order;
        
        if ($tournamentMatch->order % 2 == 0) {
            $orders[] = $tournamentMatch->order + 1;
        }else {
            $orders[] = $tournamentMatch->order - 1;
        }
        $round = $tournamentMatch->round;

        $lastTimeRound = DB::table("tournament_matches")
            ->where('tournament_matches.deleted_at', NULL)
            ->whereNotNull('tournament_matches.league_number')
            ->where( 'tournament_matches.category_id', '=', $tournamentMatch->category_id)
            ->whereNotNull('tournament_matches_date_court.tournament_match_id')
            ->join("tournament_matches_date_court","tournament_matches_date_court.tournament_match_id", "=", "tournament_matches.id")
            ->where('tournament_matches_date_court.deleted_at', NULL)
            ->max('tournament_matches_date_court.date');

        return $lastTimeRound;
        
    }


   
}
