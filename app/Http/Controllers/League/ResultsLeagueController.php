<?php

namespace App\Http\Controllers\League;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Team\Team;
use App\Models\Journey\Game;
use Illuminate\Http\Request;
use App\Models\League\League;
use App\Models\Journey\Journey;
use App\Models\Journey\GameItem;
use App\Models\Category\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\Journey\JourneyController;

class ResultsLeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    public function rankingPerCategory( Request $request ){
        
        $leagueId = $request->league_id;
        
        $league = League::findOrFail($leagueId);
        $this->authorize('view', $league);

        //Redis::del('ranking_per_category_league#'.$leagueId);
        $cacheRankingPerCategoryLeague = Redis::get('ranking_per_category_league#'.$leagueId); 
        
        if(isset($cacheRankingPerCategoryLeague)) {
           $categoryData = json_decode($cacheRankingPerCategoryLeague, FALSE);
        }else{  
            $categories = Category::where('league_id', $leagueId)->get();
            $resultByCategory = $categories->map(function($category){
                return [
                    'category_id'=> $category->id,
                    'category_name' => $category->name,
                    'teams' => $this->getRankingTeamByCategory($category)
                    ];
                }); 
            $categoryData = [
                'message' => 200,
                'result' => $resultByCategory,
            ];
            Redis::set('ranking_per_category_league#'.$leagueId, json_encode($categoryData),'EX', 3600);
        }

        return response()->json($categoryData);
    } 


    public function getRankingTeamByCategory( $category){


         $result = $category->teams->map(function($team){
            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'club_name' => $team->club->name,
                'result' => DB::table("team_results")
                    ->where('team_results.deleted_at', NULL)
                    ->where('team_results.team_id', $team->id)
                    ->select(
                        DB::raw("SUM(team_results.total_points) as points"),
                        DB::raw("SUM(team_results.match_won) as match_won"),
                        DB::raw("SUM(team_results.match_lost) as match_lost"),
                        DB::raw("SUM(team_results.sets_won) as sets_won"),
                        DB::raw("SUM(team_results.sets_lost) as sets_lost")
                    )
                    ->get()
                ];
            }); 
        
        
        
        $ranking = $result->map(function($item){
            return [
                'team_id' => $item['team_id'],
                'team_name' => $item['team_name'],
                'club_name' => $item['club_name'],
                'total_points' => $item['result'][0]->points ? $item['result'][0]->points : 0,
                'match_won' => $item['result'][0]->match_won ? $item['result'][0]->match_won: 0,
                'match_lost' => $item['result'][0]->match_lost ? $item['result'][0]->match_lost: 0,
                'sets_won' => $item['result'][0]->sets_won ? $item['result'][0]->sets_won : 0,
                'sets_lost' => $item['result'][0]->sets_lost ? $item['result'][0]->sets_lost : 0
            ];
        });

        return $ranking;
        
    }


    public function categoryDetails( $categoryId){

        $cacheDetailsCategory = Redis::get('details_category_#'.$categoryId);
        $category = Category::findOrFail($categoryId);
        
        $this->authorize('view', $category->league);

        if(isset($cacheDetailsCategory)) {
            $categoryDetailsData = json_decode($cacheDetailsCategory, FALSE);
         }else{  
            
            $teams = Team::where('category_id', $categoryId)->get();

            $result = $category->teams->map(function($team){
                return [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'club_name' => $team->club->name,
                    'result' => DB::table("team_results")
                        ->where('team_results.deleted_at', NULL)
                        ->where('team_results.team_id', $team->id)
                        ->select(
                            DB::raw("SUM(team_results.total_points) as points"),
                            DB::raw("SUM(team_results.match_won) as match_won"),
                            DB::raw("SUM(team_results.match_lost) as match_lost"),
                            DB::raw("SUM(team_results.sets_won) as sets_won"),
                            DB::raw("SUM(team_results.sets_lost) as sets_lost")
                        )
                        ->get()
                    ];
                }); 

            $rankingTeams = $result->map(function($item){
                return [
                    'team_id' => $item['team_id'],
                    'team_name' => $item['team_name'],
                    'club_name' => $item['club_name'],
                    'total_points' => $item['result'][0]->points ? $item['result'][0]->points : 0,
                    'match_won' => $item['result'][0]->match_won ? $item['result'][0]->match_won: 0,
                    'match_lost' => $item['result'][0]->match_lost ? $item['result'][0]->match_lost: 0,
                    'sets_won' => $item['result'][0]->sets_won ? $item['result'][0]->sets_won : 0,
                    'sets_lost' => $item['result'][0]->sets_lost ? $item['result'][0]->sets_lost : 0
                ];
            });


            $matches = Game::where('category_id', $categoryId)->get();
            $journeys = $matches->map(function($team){
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
            ;

           /* $rankingPlayers = DB::table('players')
                            ->join("team_players","team_players.player_id", "=", "players.id")
                            ->join("teams","teams.id", "=", "team_players.team_id")
                            ->where("teams.category_id", $categoryId)
                            ->join("player_results","player_results.player_id", "=", "players.id")
                            ->select(
                                DB::raw("CONCAT(players.name, ' ', players.surname) as name, players.avatar"),
                                DB::raw("SUM(games_won) as wins"),
                                DB::raw("SUM(games_lost) as lost"),
                                DB::raw("SUM(games_won) - SUM(games_lost) as dif"),
                                DB::raw("teams.name as club")
                            )
                            ->groupBy("player_results.player_id")
                            ->groupBy("teams.name")
                            ->orderBy("dif",'desc')
                            ->take(5)
                            ->get();
        */
        $rankingPlayers = DB::select("SELECT pr.player_id, CONCAT(p.name, ' ', p.surname) as player_name, p.avatar, c.name as club_name,
                        SUM(pr.games_won) AS wins, SUM(pr.games_lost) AS lost,
                        SUM(pr.games_won) - SUM(pr.games_lost) AS dif
                    FROM player_results pr 
                    INNER JOIN players p ON pr.player_id = p.id
                    INNER JOIN clubs c ON c.id = p.club_id
                    INNER JOIN team_players tp ON tp.player_id = p.id
                    INNER JOIN teams t ON tp.team_id = t.id 
                    WHERE t.category_id = ?
                    GROUP BY pr.player_id
                    ORDER BY dif DESC LIMIT 5", [$categoryId]);

            $graphicTeams = DB::table('team_results')
                        ->join("teams","teams.id", "=", "team_results.team_id")
                        ->select(
                            DB::raw("teams.id as team_id"),
                            DB::raw("teams.name"),
                            DB::raw("team_results.journey_id as journey"),
                            DB::raw("SUM(total_points) as points")
                        )
                        ->groupBy("team_results.team_id")
                        ->groupBy("team_results.journey_id")
                        ->orderBy("team_results.journey_id")
                        ->get();


            $categoryDetailsData = [
                'message' => 200,
                'teams' => $teams,
                'result' => $category,
                'ranking_teams' => $rankingTeams,
                'journeys' => $journeys,
                'ranking_players' => $rankingPlayers,
                'graphic_teams' => $graphicTeams
            ];
            Redis::set('details_category_#'.$categoryId, json_encode($categoryDetailsData),'EX', 3600);
        }
        return response()->json($categoryDetailsData);
    }


    public function getMoreDetailsCategory( $categoryId) {
        

        return response()->json([
            'message' =>  200,
            'visualisations' => 124
        ]);
/*
        $category = Category::findOrFail($categoryId);
        $this->authorize('view', $category->league);
        
        $last = DB::table('game_items')
            ->where('category_id', $categoryId)
            ->latest('cron_executed_at')
            ->first();

        
        $visualisations = $category->visualisations + 1;
        $category->update(['visualisations' => $visualisations]);
        
        return response()->json([
            'last_update' =>  Carbon::parse($last->cron_executed_at)->format("d M Y"),
            'visualisations' => $visualisations
        ]);
*/

    }



}
