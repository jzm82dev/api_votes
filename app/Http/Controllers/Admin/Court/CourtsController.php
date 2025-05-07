<?php

namespace App\Http\Controllers\Admin\Court;

use Carbon\Carbon;
use App\Models\Court\Court;
use Illuminate\Http\Request;
use App\Models\Court\CourtDay;
use App\Models\Court\Schedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Court\CourtCollection;
use App\Http\Resources\Court\CourtResource;
use App\Models\Court\CourtDaySchedule;
use App\Models\Court\CourtScheduleHour;
use App\Models\Tournament\Tournament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CourtsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request )
    {
        $search = $request->search;
        
        $courts = Court::where ('name', 'like', '%'.$search.'%')
                     ->where('club_id', auth("api")->user()->club_id)
                     ->orderBy('id', 'asc')
                     ->paginate(20);

        return response()->json([
            "total" => $courts->total(),
            "courts" => CourtCollection::make($courts)
        ]);
    }
    
    public function config()
    {
        $hoursDay = collect([]);

        $courtScheduleHours = Schedule::all();
        foreach ($courtScheduleHours->groupBy("hour") as $key => $scheduleHours) {
            $hoursDay->push([
                'hour' => $key,
                "format_hour" =>Carbon::parse(date("Y-m-d ".$key.':i:s'))->format("G"),
                "format_hour_start" =>Carbon::parse(date("Y-m-d ".$key.':i:s'))->format("G:i"),
                'items' => $scheduleHours->map(function($hour_item){
                    // Y-m-d h:i:s 2023-10-25 00:30:51
                    return [
                        "id" => $hour_item->id,
                        "hour_start" => $hour_item->hour_start,
                        "hour_end" => $hour_item->hour_end,
                        "format_hour_start" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_start)->format("G:i"),
                        "format_hour_end" =>Carbon::parse(date("Y-m-d").' '.$hour_item->hour_end)->format("G:i"),
                        "hour" => $hour_item->hour,
                    ];
                })
            ]);
        }
        

        return response()->json([
            "message" => 200,
            "hours_days" => $hoursDay
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
     
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'sport_type' => 'required|integer',
            'description' => 'max:191',
            'amount_without_light' => 'decimal:0,2',
            'amount_with_light' => 'decimal:0,2',
            'amount_member_without_light' => 'decimal:0,2',
            'amount_member_with_light' => 'decimal:0,2'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        
        $validCourt = Court::where('name', $request->name)
                            ->where('sport_type', $request->sport_type)
                            ->where('club_id', auth("api")->user()->club_id)
                            ->first();

        if($validCourt){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un pista en el club con este nombre'
            ]);
        }

        $request->request->add(['club_id' => auth("api")->user()->club_id]);
        
        if($request->hasFile('imagen')){
            $path = Storage::putFile("courts", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        $court = Court::create($request->all());

        return response()->json([
            "message" => 200,
            "message_text" => 'Pista almacenada correctamente',
            'court_id' => $court->id 
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $court = Court::where('club_id', auth("api")->user()->club_id)
                           ->where('id', $id) 
                           ->first();
        
        return response()->json( [
            'court' => CourtResource::make($court)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'description' => 'max:191',
            'amount_without_light' => 'decimal:0,2',
            'amount_with_light' => 'decimal:0,2',
            'amount_member_without_light' => 'decimal:0,2',
            'amount_member_with_light' => 'decimal:0,2'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $validCourt = Court::where('name', $request->name)
                            ->where('club_id', auth("api")->user()->club_id)
                            ->where('sport_type', $request->sport_type)
                            ->where('id', '<>', $id) 
                            ->first();
        

        if($validCourt){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un pista con ese nombre'
            ]);
        }


        $court = Court::where('club_id', auth("api")->user()->club_id)
                        ->where('id', $id) 
                        ->first();


        if($request->hasFile('imagen')){
            if( $court->avatar){
                Storage::delete($court->avatar);
            }
            $path = Storage::putFile("courts", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        $court->update($request->all());

        

        return response()->json([
            'message' => 200,
            'user' => $court,
            'message_text' => 'Pista actualizada correctamente'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $clubId = auth("api")->user()->club_id;
        $court = Court::where('club_id', auth("api")->user()->club_id)
                ->where('id', $id) 
                ->first();
        
        // Check if there are any match in active tournament
        $activeTournaments = Tournament::where('club_id', $clubId)
            ->where('sport_type', $court->sport_type)
            ->where('draw_generated', 1)->get();
        if( $activeTournaments->count() > 0 ){
            foreach ($activeTournaments as $tournament) {
                if( $tournament->isFinisched() == false){
                    return response()->json([
                            'message' => 422,
                            'errors_text' => 'Esta pista está asignada a torneos activos'
                    ]);
                }
            }
        }

        $existsReservation = DB::table("reservations")
            ->where('reservations.deleted_at', NULL)
            ->where('club_id', $clubId)
            ->whereDate('reservations.date', '>=', now()->format("Y-m-d"))
            ->where('reservations.court_id', '=', $id)
            ->get();
        if( $existsReservation->count() > 0 ){
            return response()->json([
                'message' => 422,
                'errors_text' => 'Existen reservas en la pista que quieres eliminar'
            ]);
        }else{
            
            $court->delete();

            return response()->json([
                'message' => 200
            ]);
        }
    }
}
