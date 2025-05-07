<?php

namespace App\Http\Controllers\Team;

use App\Models\Club\Club;
use App\Models\Team\Team;
use Illuminate\Http\Request;
use App\Models\League\League;
use App\Models\Player\Player;
use App\Models\Team\TeamPlayer;
use App\Models\Category\Category;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\Team\TeamResource;
use App\Http\Resources\Team\TeamColletion;
use App\Http\Resources\Player\PlayerColletion;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
        $search = $request->search;
        
        $teams = Team::where("name", 'like', '%'.$search.'%')
        ->orderBy('id', 'desc')
        ->paginate(20);

        return response()->json([
            "total" => $teams->total(),
            "teams" => TeamColletion::make($teams)
        ]);
    }


    public function config(){
        $clubs = Club::all();
        $categories = Category::all();
        $leagues = League::all();

        return response()->json([
            'message' => 200,
            'clubs' => $clubs,
            'leagues' => $leagues,
            'categories' => $categories
        ]);
    }    

    public function getCategoriesByLeague( Request $request){
        $leagueId = $request->league_id;
        Log::info("LeagueId: ".$leagueId);
        $categories = Category::where('league_id', $leagueId)
                               ->get();

        return response()->json([
            'message' => 200,
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validTeam = Team::where('name', $request->name)
                        ->where('club_id', $request->club_id)
                        ->where('category_id', $request->category_id) 
                        ->first();
        
        if( $validTeam ){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya hay un equipo para el club en esta categoría registrado en el sistema'
            ]);
        }

        $newTeam = Team::create($request->all());
        
        return response()->json([
            'message' => 200,
            'id_team' => $newTeam->id
        ]);

    }

    public function getPossiblePlayers(Request $request){
        $clubId = $request->club_id;
        $players = Player::where('club_id', $clubId)
                         ->get();

        return response()->json([
            'message' => 200,
            "players" => PlayerColletion::make($players)
        ]);
    }

    public function addPlayers(Request $request){
        $teamId = $request->id_team;
        $players =  json_decode($request->player_selected, 1);
        foreach ($players as $key => $value) {
            TeamPlayer::create([
                'player_id' => $value['id'],
                'team_id' => $teamId         
            ]);
        }
        
        return response()->json([
            'message' => 200
        ]);
    }


    public function deletePlayer(Request $request){
        $teamId = $request->id_team;
        $playerId = $request->id_player;

        $teamplayer = TeamPlayer::where('player_id', $playerId)
                  ->where('team_id', $teamId)
                  ->first();

        $teamplayer->delete();
        
        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $team = Team::findOrFail($id);
        
        return response()->json( [
            'team' => TeamResource::make($team)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invalidTeam = Team::where('name', $request->name)
                           ->where('club_id', $request->club_id)
                           ->where('category_id', $request->category_id) 
                           ->where('id', '<>', $id) 
                           ->first();

        if($invalidTeam){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un equipo en esa categoría con el mismo nombre'
            ]);
        }
        
        $teamUpdate = Team::findOrFail($id);
        $teamUpdate->update($request->all());

        return response()->json([
            'message' => 200,
            'team' => $teamUpdate
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $teamDelete = Team::findOrFail($id);

        $teamplayer = TeamPlayer::where('team_id', $id)->get();
        foreach ($teamplayer as $key => $teamPlayer) {
            $teamPlayer->delete();
        }

        $teamDelete->delete();

        return response()->json([
            'message' => 200
        ]);
    }
    
}
