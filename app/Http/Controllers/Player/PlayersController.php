<?php

namespace App\Http\Controllers\Player;

use Illuminate\Http\Request;
use App\Models\Player\Player;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Player\PlayerColletion;
use App\Http\Resources\Player\PlayerResource;
use App\Models\Club\Club;
use Lcobucci\JWT\Token\Plain;

class PlayersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
        $search = $request->search;
        
        $players = Player::where(DB::raw("CONCAT(players.name, ' ',players.surname, ' ',IFNULL(players.email, ''))") , 'like', '%'.$search.'%')
        ->orderBy('name', 'asc')
        ->paginate(20);

        return response()->json([
            "total" => $players->total(),
            "players" => PlayerColletion::make($players)
        ]);
    }


    public function config()
    {    
        $clubs = Club::all();
        
        return response()->json([
            "clubs" => $clubs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validPlayer = Player::where('name', $request->name)
                             ->where('surname', $request->surname)
                             ->where('club_id', $request->club_id)
                             ->first();
        
        if( $validPlayer ){
            return response()->json([
                'message' => 403,
                'message_text' => 'Hay un jugador con ese teléfono registrado en el sistema'
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("players", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        Player::create($request->all());
        
        return response()->json([
            'message' => 200
        ]);


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $player = Player::findOrFail($id);
        
        return response()->json( [
            'player' => PlayerResource::make($player)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invalidClub = Player::where('name', $request->name)
                           ->where('surname', $request->surname)
                           ->where('id', '<>', $id) 
                           ->where('club_id', $request->club_id)
                           ->first();

        if($invalidClub){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un jugador con este nombre en el club'
            ]);
        }
        
        $player = Player::findOrFail($id);
        
        if($request->hasFile('imagen')){
            if( $player->avatar){
               $result = Storage::delete($player->avatar);
            }
            $path = Storage::putFile("clubs", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }

        $player->update($request->all());

        return response()->json([
            'message' => 200,
            'club' => $player
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $player = Player::findOrFail($id);
        if( $player->avatar){
            Storage::delete($player->avatar);
        }

        $player->delete();

        return response()->json([
            'message' => 200
        ]);

    }
}
