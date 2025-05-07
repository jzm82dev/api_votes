<?php

namespace App\Http\Controllers\League;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Court\Court;
use Illuminate\Http\Request;
use App\Models\Couple\Couple;

use App\Models\League\League;
use GuzzleHttp\Psr7\Response;
use function PHPSTORM_META\map;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CouplePlayer;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaguesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
        $this->authorize('viewAny', League::class);

        $search = $request->search;
        $clubId = auth("api")->user()->club_id;
        
        $leagues = League::where('club_id', $clubId)
                     ->where("name" , 'like', '%'.$search.'%')
                     ->orderBy('start_date', 'asc')
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
                    "preregistrations" => self::preregistrations($item->id),
                    "match_finished" => self::leagueMatchFinished($item->id),
                    "match_pending" => self::leagueMatchPending($item->id)
                ];
            })
        ]);
    }


    public static function leagueMatchFinished($leagueId){
        
        $matchFinished = DB::table("journeys")
            ->where('journeys.deleted_at', NULL)
            ->where('journeys.league_id', $leagueId)
            ->join("journey_matches","journey_matches.journey_id", "=", "journeys.id")
            ->where('journey_matches.deleted_at', NULL)
            ->where('journey_matches.match_finished', '=', 1)
            ->count();

        return $matchFinished;
    }

    public static function preregistrations($leagueId){
        $journeysCreated = DB::table("journeys")
            ->where('journeys.deleted_at', NULL)
            ->where('journeys.league_id', $leagueId)
            ->count();
        if($journeysCreated == 0)
            return true;

        return false;
    }


    public function config(){
        $clubId = auth("api")->user()->club_id;
        $courts = Court::where('club_id', $clubId)->get();

        return response()->json([
            'message' => 200,
            'courts' => $courts
        ]);

    }

    public static function leagueMatchPending($leagueId){
        
        $matchPending = DB::table("journeys")
            ->where('journeys.deleted_at', NULL)
            ->where('journeys.league_id', $leagueId)
            ->join("journey_matches","journey_matches.journey_id", "=", "journeys.id")
            ->where('journey_matches.deleted_at', NULL)
            ->where('journey_matches.match_finished', '=', 0)
            ->count();

        return $matchPending;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'sport_type' => 'required|integer',
            'start_date' => 'required',
            'points_per_win_2_0' => 'required|integer', 
            'points_per_win_2_1' => 'required|integer', 
            'points_per_lost_0_2' => 'required|integer', 
            'points_per_lost_1_2' => 'required|integer'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }
         
        $clubId = auth("api")->user()->club_id;

        $validLeague = League::where('name', $request->name)
                        ->where('club_id', $clubId)
                        ->where('sport_type', $request->sport_type)
                        ->first();

        if($validLeague){
            $errors[] = 'Ya existe una liga con este nombre';
            return response()->json([
                'message' => 403,
                'errors_text' => $errors
            ]);
        }

        $this->authorize('create', League::class);

        if($request->hasFile('imagen')){
            $path = Storage::putFile("leagues", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->start_date);
        $request->request->add(["start_date" => Carbon::parse($date_clean)->format("Y-m-d")]);
        
        $request->request->add(['club_id' => $clubId]);

        $leagueInserted = League::create($request->all());
        
        
        return response()->json([
            'message' => 200,
            'id_league' => $leagueInserted->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {   
        $league = League::findOrFail($id);

        $this->authorize('view', $league);

        $matchsFiniched = self::leagueMatchFinished($id);

        $league['avatar'] = env("APP_URL")."storage/".$league->avatar;
       // $league['categories'] = $league->categories;

        $league['category'] = $league->categories->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "total_couple" => Couple::where('category_id', $item->id)->count(), 
            ];
        });

        $league['type_match'] = ($league->match_type == 'double') ? 1 : 2;
        
        return response()->json( [
            'message' => 200,
            'match_finiched' => $matchsFiniched,
            'league' => $league
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'sport_type' => 'required|integer',
            'start_date' => 'required', //|digits:9',
           'points_per_win_2_0' => 'required|integer', 
            'points_per_win_2_1' => 'required|integer', 
            'points_per_lost_0_2' => 'required|integer', 
            'points_per_lost_1_2' => 'required|integer'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       $invalidLeague = League::where('name', $request->name)
                           ->where('id', '<>', $id) 
                           ->first();

        if($invalidLeague){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe una liga con este nombre'
            ]);
        }
        
        $league = League::findOrFail($id);

        $this->authorize('update', $league);
        
        if($request->hasFile('imagen')){
            if( $league->avatar){
               $result = Storage::delete($league->avatar);
            }
            $path = Storage::putFile("leagues", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->start_date);
        $request->request->add(["start_date" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $league->update($request->all());

        return response()->json([
            'message' => 200,
            'league' => $league
        ]);
    }


    public function getAllPlayers( Request $request){
        
        $leagueId = $request->league_id;

        $league = League::findOrFail($leagueId);
        $this->authorize('view', $league);

        $playerName = $request->player_name_search;
        $status = $request->status_match_id;
        $categoryId = $request->category_id;

        $payerLeague = DB::table("couples")
            ->where('couples.deleted_at', NULL)
            ->where('couples.league_id', $leagueId)
            ->join('couple_players', 'couple_players.couple_id', '=', 'couples.id')
            ->where('couple_players.deleted_at', NULL)
            ->join('categories', 'categories.id', '=', 'couples.category_id')
            ->where('categories.deleted_at', NULL)
            ->join('users', 'couple_players.user_id', '=', 'users.id')
            ->join('club_users', 'club_users.user_id', '=', 'users.id')
            ->where('club_users.deleted_at', NULL)
            ->where('club_users.status', 'ACCEPT');

            if( $playerName && $playerName != '' ){
                $payerLeague->where(DB::raw("CONCAT(club_users.name, ' ',club_users.surname)") , 'like', '%'.$playerName.'%');
            }

            if( $status == 'PENDING' || $status == 'PAID' ){
                $payerLeague->where('couple_players.paid_status', $status);
            }
           
            if( $categoryId ){
                $payerLeague->where('couples.category_id', $categoryId);
            }
            
            $payerLeague = $payerLeague->select(
                'couple_players.id',
                'categories.name',
                'club_users.name as player_name',
                'club_users.surname as player_surname',
                'couple_players.paid_status')
            ->orderBy('club_users.name', 'asc')
            ->paginate(10);
        

        return response()->json([
            'message' => 200,
            'players' => $payerLeague,
            'total' => $payerLeague->total(),
            'categories' => $league->categories
        ]);

    }

    public function paidPlayerLeague(string $couplePlayerId){

        $user = auth("api")->user();
        $couplePlayer = CouplePlayer::findOrFail($couplePlayerId);

        if($couplePlayer != false){
            $couplePlayer->update(['paid_status' => 'PAID']);
        }
    
        return response()->json([
            'message' => 200
        ]);
    }


    public function unpaidPlayerLeague(string $couplePlayerId){

        $user = auth("api")->user();
        $couplePlayer = CouplePlayer::findOrFail($couplePlayerId);

        if($couplePlayer != false){
            $couplePlayer->update(['paid_status' => 'PENDING']);
        }
    
        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $league = League::findOrFail($id);

        $this->authorize('delete', $league);
        
        if( $league->avatar){
            Storage::delete($league->avatar);
        }

        $league->delete();

        return response()->json([
            'message' => 200
        ]);
    }
}
