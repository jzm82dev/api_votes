<?php

namespace App\Http\Controllers\League;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Couple\Couple;
use App\Models\Couple\CoupleNotPlayHourTournament;
use Illuminate\Support\Facades\Validator;

class CoupleNotPlayHourTournamentController extends Controller
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
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'couple_id' => 'required|integer',
            'date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

       // $clubId = auth("api")->user()->club_id;
       // $couple = Couple::findOrFail( $request->couple_id);
        
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $coupleNotPlay = CoupleNotPlayHourTournament::create([
            'tournament_id' => $request->tournament_id,
            'couple_id' => $request->couple_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return response()->json([
            'message' => 200,
            'schedule_id' => $coupleNotPlay->id
        ]);
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
        $schedule = CoupleNotPlayHourTournament::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'message' => 200
        ]);
    }
}
