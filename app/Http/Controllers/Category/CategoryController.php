<?php

namespace App\Http\Controllers\Category;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Court\Court;
use Illuminate\Http\Request;
use App\Models\Couple\Couple;
use App\Models\League\League;
use App\Models\Player\Player;
use App\Models\Journey\Journey;
use App\Models\Category\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CouplePlayer;
use App\Models\Couple\CoupleResult;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Journey\JourneyMatch;
use App\Models\Tournament\Tournament;
use Illuminate\Support\Facades\Validator;
use App\Models\Tournament\TournamentMatch;
use App\Http\Resources\Couple\CoupleCollection;
use App\Models\Member\ClubUser;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        
        $categories = Category::where("name" , 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     ->paginate(20);

        return response()->json([
            "total" => $categories->total(),
            "categories" => $categories->map(function($item){
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "description" => $item->description,
                    "league" => [
                        "id" => $item->league->id,
                        "name" => $item->league->name,
                    ],
                    "created_at" => $item->created_at->format("Y-m-d h:i:s")
                ];
            })
        ]);
    }


    public function config(){
        $leagues = League::all();
        
        return response()->json([
            "leagues" => $leagues
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'league_id' => 'integer',
            'tournament_id' => 'integer'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        if( $request->exists('league_id') == false && $request->exists('tournament_id') == false){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ha habido un error al guardar los datos'
            ]);
        }

        if( $request->exists('league_id')){
            $validCategory = Category::where('name', $request->name)
                                    ->where('league_id', $request->league_id)
                            ->first();

            if($validCategory){
                return response()->json([
                    'message' => 403,
                    'message_text' => 'Ya existe una categoría con este nombre'
                ]);
            }
        }

        if( $request->exists('tournament_id')){
            $validCategory = Category::where('name', $request->name)
                                    ->where('tournament_id', $request->tournament_id)
                                    ->where('match_type', $request->match_type)
                            ->first();

            if($validCategory){
                return response()->json([
                    'message' => 403,
                    'message_text' => 'Ya existe una categoría con este nombre'
                ]);
            }
        }

        $category = Category::create($request->all());
        
        
        return response()->json([
            'category_id' => $category->id,
            'message' => 200
        ]);
    }


    public function addCouple(Request $request){
        
        $clubId = auth("api")->user()->club_id;

        $isSingle = false;
        if( $request->exists('type_save') &&  $request->type_save == 'player' ){
            $isSingle = true;
        }
        
        if( $isSingle == false ){
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'player_1_mobile' => 'required|max:50',
                'player_1_name' => 'required|max:191',
                'player_1_surname' => 'required|max:191',
                'player_2_mobile' => 'max:50',
                'player_2_name' => 'max:191',
                'player_2_surname' => 'max:191',
                'substitute_mobile' => 'max:50',
                'substitute_name' => 'max:191',
                'substitute_surname' => 'max:191',
                'couple_name' => 'max:191'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'player_1_mobile' => 'required|max:50',
                'player_1_name' => 'required|max:191',
                'player_1_surname' => 'required|max:191'
            ]);
        }

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

         if( $request->exists('player_2_name') || $request->exists('player_2_surname') || $request->exists('player_2_mobile')){
            $validator = Validator::make($request->all(), [
                'player_2_mobile' => 'required|max:50',
                'player_2_name' => 'required|max:191',
                'player_2_surname' => 'required|max:191'
            ]);
            if($validator->fails()){
                $errors = get_errors($validator->errors());
    
                return response()->json([
                     'message' => 422,
                     'errors_text' => $errors
                 ]);
             }
         }


         if( (strlen($request->substitute_mobile) > 0 && (strlen($request->substitute_name) == 0 || strlen($request->substitute_surname) == 0)) 
            || (strlen($request->substitute_name) > 0 && (strlen($request->substitute_mobile) == 0 || strlen($request->substitute_surname) == 0)) 
            || (strlen($request->substitute_surname) > 0 && (strlen($request->substitute_name) == 0 || strlen($request->substitute_mobile) == 0)) )
        {
            $errors[] = "Es obligatorio teléfono, nombre y apellidos del jugador sustituto";
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);

        }

        if($isSingle){
            $coupleName = 'Single';
        }else{
            if( $request->couple_name == '' ){
                $coupleName = $request->player_1_name;
                if( $request->exists('player_2_name') ){ 
                    $coupleName = $request->player_1_name.'-'.$request->player_2_name;
                }
            }else{
                $coupleName = $request->couple_name;
            }
        }

        $category = Category::findOrFail( $request->category_id);
        $newCouple = Couple::create([
            'club_id' => $clubId,
            'category_id' => $request->category_id,
            'league_id' => $category->league_id,
            'tournament_id' => $category->tournament_id,
            'name' => $coupleName 
        ]);
         
        self::playerToCouple($newCouple->id, $request->player_1_id, $request->player_1_name, $request->player_1_surname, $request->player_1_mobile, 0);
        if( $isSingle == false && $request->exists('player_2_name')){
            self::playerToCouple($newCouple->id, $request->player_2_id, $request->player_2_name, $request->player_2_surname, $request->player_2_mobile, 0);
        }

        if( strlen($request->substitute_mobile) > 0 && strlen($request->substitute_name) > 0 && strlen($request->substitute_surname) > 0){
            $playerSubstitute = self::playerToCouple($newCouple->id, $request->substitute_id, $request->substitute_name, $request->substitute_surname, $request->substitute_mobile, 1);
        }

         $couples = Couple::where('category_id', $request->category_id)->get();
         return response()->json([
            'message' => 200,
            'couples' => CoupleCollection::make($couples)
        ]);


    }


    public function playerToCouple($coupleId, $playerId, $name, $surname, $mobile, $isSubstitute = 0, $playerIdNew = null){
        $clubId = auth("api")->user()->club_id;
        $user = User::where("mobile", $mobile)->first();
        if( $user ){
            $clubUser = ClubUser::where('user_id', $user->id)->where('club_id', $clubId)->first();
            if( $clubUser == false){
                ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
            }
            CouplePlayer::create([
                'user_id' => $user->id,
                'couple_id' => $coupleId,
                'substitute' => $isSubstitute
            ]); 
        }else{
            $user = User::create(['name' => $name, 'surname' => $surname, 'mobile' => $mobile, 'email' => 'default@example.com', 'password' => 'player']);
            ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
            CouplePlayer::create([
                'user_id' => $user->id,
                'couple_id' => $coupleId,
                'substitute' => $isSubstitute
            ]);
        }
    }

    public function editCouplesPlayer($coupleId, $playerId, $name, $surname, $mobile, $isSubstitute = 0, $playerIdNew = null){
        
        $clubId = auth("api")->user()->club_id;
        $user = User::where("mobile", $mobile)->first();

        if( $user ){
            $clubUser = ClubUser::where('user_id', $user->id)->first();
            if( $clubUser == false){
                ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
                DB::table('couple_players')->where('couple_id', '=', $coupleId)->where('user_id', $playerId)->delete();
                CouplePlayer::create([
                    'user_id' => $user->id,
                    'couple_id' => $coupleId,
                    'substitute' => $isSubstitute
                ]);
            }else{
                $clubUser->update([
                    'name' => $name,
                    'surname' => $surname,
                ]);
                if( $playerId != $user->id){
                    DB::table('couple_players')->where('couple_id', '=', $coupleId)->where('user_id', $playerId)->delete();
                    CouplePlayer::create([
                        'user_id' => $user->id,
                        'couple_id' => $coupleId,
                        'substitute' => $isSubstitute
                    ]);
                }
            }
        }else{
            DB::table('couple_players')->where('couple_id', '=', $coupleId)->where('user_id', $playerId)->delete();
            $user = User::create(['name' => $name, 'surname' => $surname, 'mobile' => $mobile, 'email' => 'default@example.com', 'password' => 'player']);
            ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
            CouplePlayer::create([
                'user_id' => $user->id,
                'couple_id' => $coupleId,
                'substitute' => $isSubstitute
            ]);
        }
          
        return $user;
    }

    public function playerToCoupleOld($coupleId, $playerId, $name, $surname, $mobile, $isSubstitute = 0, $playerIdNew = null){

        $clubId = auth("api")->user()->club_id;

        if( $playerId != null){
            $clubUser = ClubUser::where('user_id', $playerId)->where('club_id', $clubId)->first();
            if( $clubUser == false){
                ClubUser::create(['club_id' => $clubId, 'user_id' => $playerId, 'name' => $name, 'surname' => $surname]);
            }
            CouplePlayer::create([
                'user_id' => $playerId,
                'couple_id' => $coupleId,
                'substitute' => $isSubstitute
            ]); 
        }else{
            $user = User::where("mobile", $mobile)->first();
            if( false == $user){
                $user = User::create(['name' => $name, 'surname' => $surname, 'mobile' => $mobile, 'email' => 'default@example.com', 'password' => 'player']);
                ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
                CouplePlayer::create([
                    'user_id' => $user->id,
                    'couple_id' => $coupleId,
                    'substitute' => $isSubstitute
                ]);
            }
        }
       
    }

    public function editCouplesPlayerOld($coupleId, $playerId, $name, $surname, $mobile, $isSubstitute = 0, $playerIdNew = null){
        
        $clubId = auth("api")->user()->club_id;

        $user = User::findOrFail($playerId);
        if($user && $mobile == $user->mobile){
            $clubUser = ClubUser::where('user_id', $playerId)->where('club_id', $clubId)->first();
            $clubUser->update([
                'name' => $name,
                'surname' => $surname,
            ]);
        }else{
            if($playerIdNew != '' && $playerId != $playerIdNew){
                $clubUser = ClubUser::where('user_id', $playerIdNew)->where('club_id', $clubId)->first();
                if( $clubUser == false){
                    ClubUser::create(['club_id' => $clubId, 'user_id' => $playerIdNew, 'name' => $name, 'surname' => $surname]);
                }
                $couplePlayer = CouplePlayer::where('user_id', '=', $playerId)->where('couple_id', '=', $coupleId)->first();
                $couplePlayer->update(['user_id' => $playerIdNew]);
            }else{
                $user = User::create(['name' => $name, 'surname' => $surname, 'mobile' => $mobile, 'email' => 'default@example.com', 'password' => 'player']);
                ClubUser::create(['club_id' => $clubId, 'user_id' => $user->id, 'name' => $name, 'surname' => $surname]);
                $couplePlayer = CouplePlayer::where('user_id', '=', $playerId)->where('couple_id', '=', $coupleId)->first();
                $couplePlayer->update(['user_id' => $user->id]);
            }
        }




          
        return $user;
    }


   /* public function playerToCoupleOld($coupleId, $playerId, $name, $surname, $mobile, $isSubstitute = 0, $playerIdNew = null){

        $clubId = auth("api")->user()->club_id;

        if( $playerId != null){
            $player = Player::findOrFail( $playerId);
            if( $player ){
                if( $player->mobile == $mobile ){
                    $player->update([
                        'name' => $name,
                        'surname' => $surname,
                    ]);
                    $couplePlayer = CouplePlayer::where('player_id', '=', $playerId)->where('couple_id', '=', $coupleId)->first();
                    if( $couplePlayer == false){
                        CouplePlayer::create([
                            'player_id' => $playerId,
                            'couple_id' => $coupleId,
                            'substitute' => $isSubstitute
                        ]);
                    }
                }else{
                    if( $playerIdNew != null && $playerId != $playerIdNew){
                        $couplePlayer = CouplePlayer::where('player_id', '=', $playerId)->where('couple_id', '=', $coupleId)->first();
                        $couplePlayer->update(['player_id' => $playerIdNew]);
                    }else{
                        $player = Player::create([ 'club_id' => $clubId, 'name' => $name, 'surname' => $surname, 'mobile' => $mobile]);
                        //DB::table('couple_players')->where('player_id', '=', $playerId)->where('couple_id', '=', $coupleId)->delete();
                        $couplePlayer = CouplePlayer::where('player_id', '=', $playerId)->where('couple_id', '=', $coupleId)->first();
                        //CouplePlayer::create([ 'player_id' => $player->id, 'couple_id' => $coupleId, 'substitute' => $isSubstitute ]);
                        $couplePlayer->update(['player_id' => $player->id, 'substitute' => $isSubstitute ]);
                    } 
                }
            }
        }else{
            $player = Player::where("mobile", $mobile)->first();
            if( false == $player){
                $player = Player::create([ 'club_id' => $clubId, 'name' => $name, 'surname' => $surname, 'mobile' => $mobile ] );
                CouplePlayer::create([
                    'player_id' => $player->id,
                    'couple_id' => $coupleId,
                    'substitute' => $isSubstitute
                ]);
            }
        }
        return $player;
    }
    */

    public function editCouple(string $id, Request $request){
        
        $clubId = auth("api")->user()->club_id;
        $couple = Couple::findOrFail($id);

        $isSingle = false;
        if( $request->exists('type_save') &&  $request->type_save == 'player' ){
            $isSingle = true;
        }
        if( $isSingle == false ){
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'player_1_mobile' => 'required|max:50',
                'player_1_name' => 'required|max:191',
                'player_1_surname' => 'required|max:191',
                'player_2_mobile' => 'max:50',
                'player_2_name' => 'max:191',
                'player_2_surname' => 'max:191',
                'substitute_mobile' => 'max:50',
                'substitute_name' => 'max:191',
                'substitute_surname' => 'max:191',
                'couple_name' => 'max:191'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'category_id' => 'required',
                'player_1_mobile' => 'required|max:50',
                'player_1_name' => 'required|max:191',
                'player_1_surname' => 'required|max:191'
            ]);
        }


        if( $request->exists('player_2_name') || $request->exists('player_2_surname') || $request->exists('player_2_mobile')){
            $validator = Validator::make($request->all(), [
                'player_2_mobile' => 'required|max:50',
                'player_2_name' => 'required|max:191',
                'player_2_surname' => 'required|max:191'
            ]);
            if($validator->fails()){
                $errors = get_errors($validator->errors());
    
                return response()->json([
                     'message' => 422,
                     'errors_text' => $errors
                 ]);
             }
         }


        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        if( (strlen($request->substitute_mobile) > 0 && (strlen($request->substitute_name) == 0 || strlen($request->substitute_surname) == 0)) 
            || (strlen($request->substitute_name) > 0 && (strlen($request->substitute_mobile) == 0 || strlen($request->substitute_surname) == 0)) 
            || (strlen($request->substitute_surname) > 0 && (strlen($request->substitute_name) == 0 || strlen($request->substitute_mobile) == 0)) )
        {
            $errors[] = "Es obligatorio teléfono, nombre y apellidos del jugador sustituto";
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);

        }


        $couple->update(['name' => $request->couple_name, 'category_id' => $request->couple_category_id]);

        $player1 = self::editCouplesPlayer($couple->id, $request->player_1_id, $request->player_1_name, $request->player_1_surname, $request->player_1_mobile, 0, $request->player_1_id_new);       
        if( $isSingle == false ){
            //only one player and adding the second one
            if(($request->exists('player_2_id') == false || $request->player_2_id == null) && ($request->exists('player_2_id') == true && $request->player_2_id_new != null)){
                CouplePlayer::create([
                    'user_id' => $request->player_2_id_new,
                    'couple_id' => $couple->id,
                    'substitute' => 0
                ]); 
            }else{
                if( $isSingle == false && ($request->exists('player_2_id') == false || $request->player_2_id == null) && $request->exists('player_2_name')){
                    self::playerToCouple($couple->id, null, $request->player_2_name, $request->player_2_surname, $request->player_2_mobile, 0);
                }else{    
                    $player2 = self::editCouplesPlayer($couple->id, $request->player_2_id, $request->player_2_name, $request->player_2_surname, $request->player_2_mobile, 0, $request->player_2_id_new);
                }
            }
        }
            
        if(strlen($request->substitute_mobile) > 0 && strlen($request->substitute_name) > 0 && strlen($request->substitute_surname) > 0 ){
            if( $isSingle == false && $request->exists('substitute_id')== false && $request->exists('substitute_name')){
                self::playerToCouple($couple->id, null, $request->substitute_name, $request->substitute_surname, $request->substitute_mobile, 1);
            }else{
                $playerSubstitute = self::editCouplesPlayer($couple->id, $request->substitute_id, $request->substitute_name, $request->substitute_surname, $request->substitute_mobile, 1, $request->substitute_id_new);
            }
        }

        $couples = Couple::where('category_id', $request->category_id)->get();
        
        return response()->json([
           'message' => 200,
           'couples' => CoupleCollection::make($couples)
       ]);

    }

    public function getCouple(string $id){
        
        $couple = Couple::where('id', $id)->get();
        $categories = collect();

        if( is_null($couple[0]->league) == false ){
            $categories = $couple[0]->league->categories;
        }else{
            $categories = $couple[0]->tournament->categories;
        }

        return response()->json( [
            'message' => 200,
            'categories' =>  $categories,
            'couple' => CoupleCollection::make($couple)
        ]);
    }
    
    public function getCoupleResults(string $id){
        
        $results = CoupleResult::where('couple_id', $id)->first();
        /*$matches = JourneyMatch::where('match_finished', 1)
        ->orWhere([
            ['local_couple_id', '=', $id],
            ['visiting_couple_id', '=', $id],
        ])->get();
          */
        $matches = JourneyMatch::where('local_couple_id', '=', $id)
        ->orWhere('visiting_couple_id', '=', $id)->orderBy('id', 'asc')->get();
          

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
    
    public function getPlayersByMobile(string $mobile){
        $players = Player::where("mobile" , 'like', '%'.$mobile.'%')
                     ->orderBy('id', 'desc')
                     ->get();

        return response()->json( [
            'message' => 200,
            'players' => $players
        ]);
    }

    public function getPlayersByName(string $name){
        $players = Player::where("name" , 'like', '%'.$name.'%')
                     ->orderBy('id', 'desc')
                     ->get();

        return response()->json( [
            'message' => 200,
            'players' => $players
        ]);
        
    }


    public function getPlayers( Request $data){
       
        $players = User::where('id', '>', 1);

        if( $data->mobile != ''){
            $players->where("mobile" , 'like', '%'.$data->mobile.'%');
        }
        
        if( $data->name != ''){
            $players->where("name" , 'like', '%'.$data->name.'%');
        }
        
        if( $data->surname != ''){
            $players->where("surname" , 'like', '%'.$data->surname.'%');
        }
        
        $players = $players->get();
               
        return response()->json( [
            'message' => 200,
            'players' => $players
        ]);

    }

    public function getPlayersBySurname(string $surname){
        $players = Player::where("surname" , 'like', '%'.$surname.'%')
                     ->orderBy('id', 'desc')
                     ->get();

        return response()->json( [
            'message' => 200,
            'players' => $players
        ]);
        
    }



    public function getTotalCouples(string $id){
        $category = Category::findOrFail($id);
        $totalCouples = Couple::where("category_id", $id)->count();
        
        return response()->json([
            'message' => 200,
            'total_couples' => $totalCouples,
            'category_data' => $category
        ]);
    }

    
    public function removeCouple(string $id){
        
        $couple = Couple::findOrFail($id);
        
        DB::table('couple_players')->where('couple_id', '=', $id)->delete();
        $couple->delete();

        return response()->json([
            'message' => 200
        ]);

    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        $couples = Couple::where('category_id', $id)->get();

        $journeyCreated = false;
        $totalJourneys = Journey::where('category_id', $id)->count();
        if( $totalJourneys > 0 ){
            $journeyCreated = true;
        }

        $drawCreated = false;
        $totalMatchesDraw = TournamentMatch::where('category_id', $id)->count();
        if( $totalMatchesDraw > 0 ){
            $drawCreated = true;
        }

        $matchType = 'double';
        $tournamentData = null;
        if( $category->tournament_id != null){
            $tournamentData = Tournament::findOrFail($category->tournament_id);
            $matchType = $tournamentData->match_type;
        }

        if( $category->league_id != null){
            $leagueData = League::findOrFail($category->league_id);
            $matchType = $leagueData->match_type;
        }
        
        return response()->json( [
            'message' => 200,
            'tournament' => ($tournamentData!=null) ? $tournamentData : '',
            'category' => $category,
            'type_matchs' => $matchType,
            'journeys_created' => $journeyCreated,
            'total_journeys_created' => $totalJourneys,
            'draw_created' => $drawCreated,
            //'couples_2' => $couples,
            'couples' => CoupleCollection::make($couples)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'description' => 'max:191',
         //   'league_id' => 'required|integer',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        if( $request->exists('league_id')){
            $invalidCategory = Category::where('name', $request->name)
                                    ->where('league_id', $request->league_id)
                                    ->where('id', '<>', $id)
                            ->first();

            if($invalidCategory){
                $errors[] = 'Ya existe una categoría con este nombre';
                return response()->json([
                    'message' => 422,
                    'errors_text' => $errors
                ]);
            }
        }

        if( $request->exists('tournament_id')){
            $invalidCategory = Category::where('name', $request->name)
                                    ->where('tournament_id', $request->tournament_id)
                                    ->where('id', '<>', $id)
                            ->first();

            if($invalidCategory){
                $errors[] = 'Ya existe una categoría con este nombre';
                return response()->json([
                    'message' => 422,
                    'errors_text' => $errors
                ]);
            }
        }
        
        $category = Category::findOrFail($id);  

        if( $request->description == 'null' || $request->description == ''){
            unset($request['description']);
        }

        if( $request->type == 'null' || $request->type == ''){
            unset($request['type']);
        }

        $category->update($request->all());

        return response()->json([
            'message' => 200,
            'category' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        $couples = Couple::where('category_id', $id)->get();
        foreach ($couples as $key => $couple) {
            CouplePlayer::where('couple_id', $couple->id)->delete();
            $couple->delete();
        }

        $category->delete();

        return response()->json([
            'message' => 200
        ]);
    }
}
