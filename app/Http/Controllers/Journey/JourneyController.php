<?php

namespace App\Http\Controllers\Journey;

use Carbon\Carbon;
use Dotenv\Util\Str;
use App\Models\Journey\Game;
use Illuminate\Http\Request;
use App\Models\Couple\Couple;
use App\Models\League\League;
use App\Models\Journey\Journey;
use App\Models\Journey\GameItem;
use App\Models\Category\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CoupleResult;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Journey\JourneyMatch;
use App\Models\Journey\JourneyJoinCategory;
use Illuminate\Support\Facades\Validator;

class JourneyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $categoryId = $request->category_id;

        $category = Category::findOrFail($categoryId);
        $totalCouples = Couple::where('category_id', $categoryId)->count();
        
        $jorneys = Journey::where('name' , 'like', '%'.$search.'%')
            ->where('league_id', '=', $category->league_id)
            ->where('category_id', '=', $categoryId)
            ->orderBy('id', 'asc')
            ->paginate(20);

        return response()->json([
            "total" => $jorneys->total(),
            "total_couples" => $totalCouples,
            "type_matchs" => $category->league->match_type,
            "league_id" => $category->league_id,
            "category_name" => $category->name,
            "type_category" => $category->type,
            "journeys" => $jorneys->map(function($item){
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "description" => $item->description,
                    "matchs_pending" => self::getPendingResult($item->id),
                    "date" => Carbon::parse($item->date)->format("d M Y"),
                    "craeted_at" => $item->created_at->format("Y-m-d H:m"),
                ];
            })
        ]);
    }

    public static function getPendingResult( string $journeyId){
        $totalMatchs = JourneyMatch::where('journey_id', $journeyId)->count();
        $totalMatchsFinished = JourneyMatch::where('journey_id', $journeyId)->where('match_finished', 1)->count();

        return $totalMatchs - $totalMatchsFinished;
    }


    public function createCalendar( string $categoryId){

        $isOddCouples = false;
        $category = Category::findOrFail($categoryId);
        $teams = Couple::where('category_id', $categoryId)->inRandomOrder()->get();

        $league = League::findOrFail($category->league_id);

        $startDate = $league->start_date;
        $auxDate = Carbon::parse($startDate)->format("Y-m-d h:i:s");

        $calendar = [];
        $totalTeams = $teams->count();
        
        $idTeamsArray = [];


        foreach ($teams as $value) {
            $idTeamsArray[] = $value->id;
        }


        if( $totalTeams % 2 == 1){ // numbers of couples is odd
            $idTeamsArray[] = -1;
            $isOddCouples = true;
        }
        $totalTeams = count($idTeamsArray);//;->count();
        $cont = 1;

        if( $category->type == 1 || $category->type == 6){
            for( $journey = 1; $journey < $totalTeams; $journey++){
                $date = Carbon::parse($auxDate)->format("Y-m-d h:i:s");
                $newJournew = Journey::create(['league_id' => $category->league_id, 'category_id' => $categoryId, 'name' => 'Jornada '.$cont, 'date' => $date ]);
                $matching = [];
                $rand = 0;
                for( $i = 0; $i < $totalTeams / 2; $i++){
                    $checkValidTeam1 = $i + 1;
                    $checkValidTeam2 = $totalTeams / 2 + 1;
                    $team1 = $idTeamsArray[$i];
                    $team2 = $idTeamsArray[$totalTeams - 1 - $i];
                    if ($checkValidTeam1 != $totalTeams && ($checkValidTeam2 != $totalTeams || $totalTeams == 2) && $team1 != -1 && $team2 != -1 && $team1 != $team2) {
                        //$rand = rand(0, 1);
                        $rand ++;
                        if( $rand % 2 == 0){
                            $jorneyMatch = JourneyMatch::create(['journey_id' => $newJournew->id, 'local_couple_id' => $team1, 'visiting_couple_id' => $team2]);
                        }else{
                            $jorneyMatch = JourneyMatch::create(['journey_id' => $newJournew->id, 'local_couple_id' => $team2, 'visiting_couple_id' => $team1]);
                        }
                        $matching[] = [$team1, $team2];
                    }
                }
                array_splice($idTeamsArray, 1, 0, array_pop($idTeamsArray));
                $calendar[] = $matching;
                $cont ++;
                $auxDate = strtotime("+ 7 days", strtotime($date));
            }
        }
        if($category->type == 6 ){
            for( $journey = 1; $journey < $totalTeams; $journey++){
                $date = Carbon::parse($auxDate)->format("Y-m-d h:i:s");
                $newJournew = Journey::create(['league_id' => $category->league_id, 'category_id' => $categoryId, 'name' => 'Jornada '.$cont, 'date' => $date ]);
                $matching = [];
                $rand = 0;
                for( $i = 0; $i < $totalTeams / 2; $i++){
                    $checkValidTeam1 = $i + 1;
                    $checkValidTeam2 = $totalTeams / 2 + 1;
                    $team1 = $idTeamsArray[$i];
                    $team2 = $idTeamsArray[$totalTeams - 1 - $i];
                    if ($checkValidTeam1 != $totalTeams && ($checkValidTeam2 != $totalTeams || $totalTeams == 2) && $team1 != -1 && $team2 != -1 && $team1 != $team2) {
                        //$rand = rand(0, 1);
                        $rand ++;
                        if( $rand % 2 == 0){
                            $jorneyMatch = JourneyMatch::create(['journey_id' => $newJournew->id, 'local_couple_id' => $team2, 'visiting_couple_id' => $team1]);
                        }else{
                            $jorneyMatch = JourneyMatch::create(['journey_id' => $newJournew->id, 'local_couple_id' => $team1, 'visiting_couple_id' => $team2]);
                        }
                        $matching[] = [$team1, $team2];
                    }
                }
                array_splice($idTeamsArray, 1, 0, array_pop($idTeamsArray));
                $calendar[] = $matching;
                $cont ++;
                $auxDate = strtotime("+ 7 days", strtotime($date));
            }
        }

        $request = new \Illuminate\Http\Request();

        $request->replace(['category_id' => $categoryId, 'search' => '']);
        $journeys = self::index($request);


        return response()->json([
            'message' => 200,
            'journeys' => $journeys->original['journeys'],
            'total' => $journeys->original['total']
        ]);

        //return $calendar;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validJuorney = Journey::where('name', $request->name)
                        ->first();

        if($validJuorney){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe una jornada con este nombre'
            ]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        
        $jorneyCreated = Journey::create($request->all());

        $categories = Category::all();
        foreach ($categories as $key => $category) {
            JourneyJoinCategory::create([
                'journey_id' => $jorneyCreated->id,
                'category_id' => $category->id
            ]);
        }
        
        return response()->json([
            'message' => 200,
            'id_journey' => $jorneyCreated->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $journey = Journey::findOrFail($id);

        return response()->json( [
            'jorney' => $journey
        ]);
    }


    public function getMatchs(string $id){
        $journey = Journey::findOrFail($id);

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
                            "name" => $player->user->name.' '.$player->user->surname
                        ];
                    }) : [],
                    //"visiting_team" => $item->visiting_couple->name,
                    "visiting_players" => $item->visiting_couple ? $item->visiting_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name.' '.$player->user->surname
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

    public function getCategories(Request $request){
        
        $idJourney = $request->journey_id;
        $categoriesJourney = JourneyJoinCategory::where('journey_id', $idJourney)->get();

        return response()->json([
            "message" => 200,
            "categories" => $categoriesJourney->map(function($item){
                return [
                    "id" => $item->id,
                    'category_id' => $item->category_id,
                    "name" => $item->category->name,
                    "teams" => $item->category->teams->map(function($team){
                        return [
                            "id" => $team->id,
                            "name" => $team->name,
                            "club_name" => $team->club->name
                        ];
                    }), 
                ];
            })
        ]);
    }

 
    public function editData( string $id, Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'date' => 'required', //|digits:9',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        $journey = Journey::findOrFail($id);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d")]);


        $journey->update($request->all());

        return response()->json([
            'message' => 200
        ]);
    }

    public function saveResult( string $id, Request $request){

        $results = json_decode($request->results, 1);
        $test = array();

        $errors = [];

        $pattern = '/^(6|7)-[0-7]$|^[0-7]-(6|7)$/';
        foreach ($results as $result) {
            $result_set_1 = $result['result_set_1'];
            if( ($result_set_1 != null && $result_set_1 != '' && strlen($result_set_1) == 3) && preg_match($pattern, $result_set_1) == false){
                $errors[] = "El resultado del Set 1 entre ".$result['local_team']." y ".$result['visiting_team']." es incorrecto.";
            }

            $result_set_2 = $result['result_set_2'];
            if( ($result_set_2 != null && $result_set_2 != '' && strlen($result_set_2) == 3) && preg_match($pattern, $result_set_2) == false){
                $errors[] = "El resultado del Set 2 entre ".$result['local_team']." y ".$result['visiting_team']." es incorrecto.";
            }

            $result_set_3 = $result['result_set_3'];
            if( ($result_set_3 != null && $result_set_3 != '' && strlen($result_set_3) == 3) && preg_match($pattern, $result_set_3) == false){
                $errors[] = "El resultado del Set 3 entre ".$result['local_team']." y ".$result['visiting_team']." es incorrecto.";
            }
        }

        if(count($errors) > 0){
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        foreach ($results as $result) {
            $jorneyMatch = JourneyMatch::findOrFail($result['id']);
            if( true == $result['change_result']){
                $jorneyMatch->update(['result_set_1' => $result['result_set_1'], 'result_set_2' => $result['result_set_2'], 'result_set_3' => $result['result_set_3'] ]);
                $test[] = $result['id'];
                self::calculatingResult($result['id']);
            }
        }
        
        return response()->json([
            'message' => 200,
            'test' => $test
        ]);
    }

    

    public function calculatingResult( string $matchId){
        
        $jorneyMatch = JourneyMatch::findOrFail( $matchId);
        $couple1Id = $jorneyMatch->local_couple_id;
        $couple2Id = $jorneyMatch->visiting_couple_id;
        
        $pointsPerWin_2_0 = $jorneyMatch->journey->league->points_per_win_2_0;
        $pointsPerWin_2_1 = $jorneyMatch->journey->league->points_per_win_2_1;
        $pointLosser_0_2 = $jorneyMatch->journey->league->points_per_lost_0_2;
        $pointLosser_1_2 = $jorneyMatch->journey->league->points_per_lost_1_2;

        self::calculatingResultTeam($couple1Id, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2);
        self::calculatingResultTeam($couple2Id, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2 );

    }

    public function calculatingResultTeam( $coupleId, $pointsPerWin_2_0, $pointsPerWin_2_1, $pointLosser_0_2, $pointLosser_1_2  ){
        
        $matchsWin = 0;
        $matchsLost = 0;
        $setsWin = 0;
        $setsLost = 0;
        $gamesWin = 0;
        $gamesLost = 0;
        $totalPoints = 0;
        $matchesPlayed = 0;

        $matchs = JourneyMatch::whereNotNull('result_set_1')
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

        $matchs = JourneyMatch::whereNotNull('result_set_1')
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
            /*if( $setsWonCurrentMatch == 2){
                $totalPoints += (int)$pointsPerWin;
                $matchsWin += 1;
                $match->update(['match_finished' => '1']);
            }elseif($setsLostCurrentMatch == 2){
                if( $setsWonCurrentMatch == 1){
                    $totalPoints += (int)$pointLosserByWinSet;
                }
                $matchsLost += 1;
                $match->update(['match_finished' => '1']);
            }else{
                $match->update(['match_finished' => '0']);
            }*/
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
        $coupleResult = CoupleResult::where('couple_id', $coupleId)->first();
        if( $coupleResult ){
            $coupleResult->delete();
        }

        CoupleResult::create([
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


    public function createGame( Request $request){
        Game::create($request->all());

        return response()->json([
            'message' => 200
        ]);
    }


    public function getGamesByCategoryJourney(Request $request){
        $matches = Game::where('journey_id', $request->journey_id)
                       ->where('category_id', $request->category_id)
                       ->get();

        
        return response()->json([
            'message' => 200,
            'games' => $matches->map(function($team){
                return [
                    "id" => $team->id,
                    "jorney_name" => $team->journey->name,
                    "journey_id" => $team->journey_id,
                    "category_id" => $team->category_id,
                    "local_team_id" => $team->local_team_id,
                    "local_team_name" => $team->local_team->name, //.'-'.$team->local_team->club->name,
                    "match_items" => GameItem::where('game_id', $team->id)->get()  ? GameItem::where('game_id', $team->id)->get() : [],
                    "local_players" => $team->local_team->players->map(function($player){
                        return [
                            "id" => $player->player->id,
                            "name" => $player->player->name//.' '.$player->player->surname
                        ];
                    }), 
                    "visiting_team_id" => $team->visiting_team_id,
                    "visiting_team_name" => $team->visiting_team->name, //.'-'.$team->visiting_team->club->name,
                    "visiting_players" => $team->visiting_team->players->map(function($player){
                        return [
                            "id" => $player->player->id,
                            "name" => $player->player->name//.' '.$player->player->surname
                        ];
                    }), 
                ];
            })
        ]);
    }


    public function getMatchItems(Request $request){
        $gameItems = GameItem::where('journey_id', $request->journey_id)
                ->where('category_id', $request->category_id)
                ->orderBy('game_id', 'ASC')
                ->get();

                return response()->json([
                    'message' => 200,
                    'game_items' => $gameItems
                ]);   
    }

    public function saveGamesBoard(Request $request){
        $gameItemUpdate = GameItem::where('game_id', $request->game_id)
                       ->where('game_number', $request->game_number)
                       ->where('journey_id', $request->journey_id)
                       ->where('category_id', $request->category_id)
                       ->first();
        if(!$gameItemUpdate){
            GameItem::create($request->all());   
        }else{
            $gameItemUpdate->update($request->all());
        }

        return response()->json([
            'message' => 200
        ]);
    }


    public function getRanking( string $categoryId ){

        $category = Category::findOrFail($categoryId);
        $rankingList = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->leftJoin("couple_results","couples.id", "=", "couple_results.couple_id")
            ->where('couple_results.deleted_at', NULL)
            ->where('couples.category_id', $categoryId)
            ->select('couple_results.*', 'couples.name', DB::raw("couples.id as couple_id"))
            ->orderBy('couple_results.total_points', 'desc')
            ->orderBy('couple_results.sets_won', 'desc')
            ->orderBy('couple_results.games_avg', 'desc')
            //->orderBy('couple_results.sets_lost', 'asc')
            ->orderBy('couples.name', 'asc')
            ->get();  

        $table = $rankingList->map(function($item) {
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
                        "title" => $player->user->name.' '.$player->user->surname
                    ];  
                }),
            ];
        });

        return response()->json([
            'message' => 200,
            'type_matchs' => $category->league->match_type,
            'ranking' => $table,
            'category_name' => $category->name,
            'league_id' => $category->league_id
        ]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invalidJuorney = Journey::where('name', $request->name)
                           ->where('id', '<>', $id) 
                           ->first();

        if($invalidJuorney){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe una jornada con este nombre'
            ]);
        }
        
        $jorney = Journey::findOrFail($id);

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        
        $jorney->update($request->all());

        return response()->json([
            'message' => 200,
            'jorney' => $jorney
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jorney = Journey::findOrFail($id);
        $jorney->delete();

        return response()->json([
            'message' => 200
        ]);
    }


    public function removeBoard(Request $request){
        $game = Game::findOrFail( $request->board_id);
        $game->delete();

        return response()->json([
            'message' => 200
        ]);

    }
}
