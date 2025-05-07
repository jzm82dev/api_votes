<?php

namespace App\Http\Controllers\Tournament;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Tournament\TournamentScheduleDayHour;

class TournamentScheduleDayHourController extends Controller
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

        //$scheduleWeeklyHours = json_decode($request->schedule_hour, 1);

        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'date' => 'required',
            'opening_time' => 'required',
            'closing_time' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        $clubId = auth("api")->user()->club_id;

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d")]);

        $tournamentSchedule = TournamentScheduleDayHour::create([
            'tournament_id' => $request->tournament_id,
            'date' => $request->date,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
        ]);

        return response()->json([
            'message' => 200,
            'schedule_id' => $tournamentSchedule->id
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
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'date' => 'required',
            'opening_time' => 'required',
            'closing_time' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }
        
        $tournamentSchedule = TournamentScheduleDayHour::findOrFail($id);
        $tournamentSchedule->update($request->all());

        return response()->json([
            'message' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $schedule = TournamentScheduleDayHour::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'message' => 200
        ]);
    }
}
