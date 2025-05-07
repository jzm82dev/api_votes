<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\League\League;
use App\Models\Team\TeamResult;
use Illuminate\Console\Command;
use App\Models\Journey\GameItem;
use App\Models\Category\Category;
use App\Models\Journey\Journey;
use Illuminate\Support\Facades\DB;
use App\Models\Player\PlayerResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UpdatingResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:updating-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update result';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        date_default_timezone_set('Europe/Madrid');

        /**
         * Remove redis cache of League and Categories
         */
            $leagues = League::all();
            foreach ($leagues as $league) {
                Redis::del('ranking_per_category_league#'.$league->id);
            }
            $categories = Category::all();
            foreach ($categories as $category) {
                Redis::del('details_category_#'.$category->id);
            }


        /*
            Para cuando se equivoquen en meter algún resultado
            TRUNCATE `team_results`;
            ALTER TABLE `team_results` AUTO_INCREMENT=1;
            TRUNCATE `player_results`;
            ALTER TABLE `player_results` AUTO_INCREMENT=1;
            UPDATE `journeys` SET `cron_executed_at`=NULL;

        */


        
      /*  $gameItems = DB::table("game_items")
                    ->where('game_items.deleted_at', NULL)
                    ->whereNull('game_items.cron_executed_at')
                    ->join("games","game_items.game_id", "=", "games.id")
                    ->select('game_items.*', 'games.visiting_team_id', 'games.local_team_id')
                    ->get();
    */
        /*    $gameItems = DB::table('journeys')
                ->where('journeys.status', '1')
                ->whereNull('journeys.cron_executed_at')
                ->where('journeys.deleted_at', NULL)
                ->join('games', 'games.journey_id', '=', 'journeys.id')
                ->where('games.deleted_at', NULL)
                ->join('game_items', 'game_items.game_id', '=', 'games.id')
                ->where('game_items.deleted_at', NULL)
                ->select('journeys.id', 'game_items.*', 'games.visiting_team_id', 'games.local_team_id')
                ->get();
*/
        $gameItems = DB::select("SELECT j.id, gi.*,g.visiting_team_id, g.local_team_id  FROM journeys j 
                                INNER JOIN games g ON j.id = g.journey_id AND g.deleted_at IS NULL 
                                INNER JOIN game_items gi ON gi.game_id = g.id
                                WHERE j.`status` = '1' AND j.cron_executed_at IS NULL");
        //Log::info(json_encode($gameItems));
        //dd();     
        //GameItem::whereNull('cron_executed_at')->get();
       
        foreach ($gameItems as $item) {
                
                $localPlayersGamesWin = 0;
                $visitingPlayersGameWin = 0;
                $localTeamSetsWin = 0;
                $visitingTeamSetsWin = 0;
                $totalPointsLocal = 0;
                $totalPointsVisiting = 0;
                $localMatchWon = 0;
                $localMatchLost = 0;
                $visitingMatchWon = 0;
                $visitingMatchLost = 0;

            // Calculate games per player
            if( $item->result_set_1 != null){
                $setResult1 = explode('-',$item->result_set_1);
                $localPlayersGamesWin += (int)$setResult1[0];
                $visitingPlayersGameWin += (int)$setResult1[1];
            }
            if( $item->result_set_2 != null){
                $setResult2 = explode('-',$item->result_set_2);
                $localPlayersGamesWin += (int)$setResult2[0];
                $visitingPlayersGameWin += (int)$setResult2[1];
            }
            if( $item->result_set_3 != null){
                $setResult3 = explode('-',$item->result_set_3);
                $localPlayersGamesWin += (int)$setResult3[0];
                $visitingPlayersGameWin += (int)$setResult3[1];
            }
            // Calculate set per team
            if( $item->result_set_1 != null){
                $setResult1 = explode('-',$item->result_set_1);
                if( (int)$setResult1[0] == 7 ){
                    $localTeamSetsWin += 1;
                }elseif((int)$setResult1[1] == 7 ){
                    $visitingTeamSetsWin += 1;
                }elseif( (int)$setResult1[0] == 6){
                    $localTeamSetsWin += 1;
                }else{
                    $visitingTeamSetsWin += 1;
                }
            }
            if( $item->result_set_2 != null){
                $setResult2 = explode('-',$item->result_set_2);
                if( (int)$setResult2[0] == 7 ){
                    $localTeamSetsWin += 1;
                }elseif((int)$setResult2[1] == 7 ){
                    $visitingTeamSetsWin += 1;
                }elseif( (int)$setResult2[0] == 6){
                    $localTeamSetsWin += 1;
                }else{
                    $visitingTeamSetsWin += 1;
                }
            }
            if( $item->result_set_3 != null){
                $setResult3 = explode('-',$item->result_set_3);
                if( (int)$setResult3[0] == 7 ){
                    $localTeamSetsWin += 1;
                }elseif((int)$setResult3[1] == 7 ){
                    $visitingTeamSetsWin += 1;
                }elseif( (int)$setResult3[0] == 6){
                    $localTeamSetsWin += 1;
                }else{
                    $visitingTeamSetsWin += 1;
                }
            }

            //$totalPointsLocal = $localTeamSetsWin;
            //$totalPointsVisiting = $visitingTeamSetsWin;

            // Calculate points depending set numbers
            if( $localTeamSetsWin == 2){
                $totalPointsLocal = 2;
                $localMatchWon = 1;
                $visitingMatchLost = 1;
            }else{
                $totalPointsVisiting = 2;
                $visitingMatchWon = 1;
                $localMatchLost = 1;
            }
            
           
           /*
           
            if( $localTeamSetsWin == 2 && $visitingTeamSetsWin == 0){
                $totalPointsLocal += 2;
                $totalPointsVisiting += 0;
                $localMatchWon = 1;
                $visitingMatchLost = 1;
            }elseif( $localTeamSetsWin == 2 && $visitingTeamSetsWin == 1){
                $totalPointsLocal += 2;
                $totalPointsVisiting += 1;
            }elseif( $localTeamSetsWin == 1 && $visitingTeamSetsWin == 2){
                $totalPointsLocal += 1;
                $totalPointsVisiting += 2;
            }else{
                $totalPointsLocal += 0;
                $totalPointsVisiting += 2;
            }
            */

            PlayerResult::create([
                'player_id' => $item->local_player_1,
                'games_won' => $localPlayersGamesWin,
                'games_lost' => $visitingPlayersGameWin
            ]);
            PlayerResult::create([
                'player_id' => $item->local_player_2,
                'games_won' => $localPlayersGamesWin,
                'games_lost' => $visitingPlayersGameWin
            ]);
            PlayerResult::create([
                'player_id' => $item->visiting_player_1,
                'games_won' => $visitingPlayersGameWin,
                'games_lost' => $localPlayersGamesWin
            ]);
            PlayerResult::create([
                'player_id' => $item->visiting_player_2,
                'games_won' => $visitingPlayersGameWin,
                'games_lost' => $localPlayersGamesWin
            ]);

            TeamResult::create([
                'team_id' => $item->local_team_id,
                'journey_id' => $item->journey_id,
                'total_points' => $totalPointsLocal,
                'match_won' => $localMatchWon,
                'match_lost' => $localMatchLost,
                'sets_won' => $localTeamSetsWin,
                'sets_lost' => $visitingTeamSetsWin
            ]); 
            TeamResult::create([
                'team_id' => $item->visiting_team_id,
                'journey_id' => $item->journey_id,
                'total_points' => $totalPointsVisiting,
                'match_won' => $visitingMatchWon,
                'match_lost' => $visitingMatchLost,
                'sets_won' => $visitingTeamSetsWin,
                'sets_lost' => $localTeamSetsWin
            ]); 
            $updateGameItem = GameItem::findOrFail($item->id);
            $updateGameItem->update([
                'cron_executed_at' => Carbon::now()
            ]);

            $journey = Journey::findOrFail( $item->journey_id);
            $journey->update([
                'cron_executed_at' => Carbon::now()
            ]);

        }
        dd('todo bien');
        
    }
}
