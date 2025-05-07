<?php

namespace App\Http\Controllers\Meeting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Urbanisation\OwnersController;
use App\Models\Meeting\Answer;
use App\Models\Meeting\Meeting;
use App\Models\Meeting\Question;
use App\Models\Meeting\Vote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class MeetingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $urbanisationId = $request->urbanisation_id;
        $building = $request->building;
        
        $meetings = Meeting::where('meetings.id','>', 0)
            ->join("urbanisations","urbanisations.id", "=", "meetings.urbanisation_id")
            ->select(
                DB::raw("meetings.id AS id"),
                DB::raw("meetings.name AS meeting_name"),
                DB::raw("urbanisations.name AS urbanisations_name"),
                DB::raw("meetings.date AS date")
            );
        if( strlen($search) > 0){
            $meetings->where(DB::raw("CONCAT(meetings.name)") , 'like', '%'.$search.'%');
        }
        if( $urbanisationId != 'undefined'){
            $meetings->where('urbanisation_id', $urbanisationId);
        }
        $meetings = $meetings->orderBy('date', 'asc')
        ->paginate(10);


        $total = Meeting::where('id','>', 0);
        if( strlen($search) > 0){
            $meetings->where(DB::raw("CONCAT(meetings.name)") , 'like', '%'.$search.'%');
        }
        if( $urbanisationId != 'undefined'){
            $meetings->where('urbanisation_id', $urbanisationId);
        }
        $total = $total->count();

        
                     
        return response()->json([
            "total" => $total,
            "meetings" => $meetings
        ]);
    }

    public static function config( ){

        $urbanisations = DB::table('urbanisations')
                  ->distinct()
                  ->get(['id', 'name']);


         return response()->json([
             'message' => 200,
             'urbanisations' => $urbanisations,
         ]);
     }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'urbanisation_id' => 'required',
            'date' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }
         
       
        $validLeague = Meeting::where('name', $request->name)
                        ->where('urbanisation_id', $request->urbanisation_id)
                        ->first();

        if($validLeague){
            $errors[] = 'Ya existe una junta con este nombre';
            return response()->json([
                'message' => 403,
                'errors_text' => $errors
            ]);
        }

       

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->date);
        $request->request->add(["date" => Carbon::parse($date_clean)->format("Y-m-d")]);
        
       
        $meetingInserted = Meeting::create($request->all());
        
        
        return response()->json([
            'message' => 200,
            'meeting_id' => $meetingInserted->id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $meeting = Meeting::findOrFail($id);

        $questions = Question::where('meeting_id', $id)->get();

        //$meeting['properties'] = $meeting->properties;
        $meeting['urbanisation'] = $meeting->urbanisation;

        return response()->json( [
            'response' => 200,
            'meeting' => $meeting,
            'questions' => $questions
        ]);

    }

    public function addQuestion( Request $request)
    {

        $validator = Validator::make($request->all(),[
            'meeting_id' => 'required',
            'name' => 'required|max:300',
            'coefficient' => 'required|between:0,1'
        ]);


        $newQuestion = Question::create($request->all());
        
        

        return response()->json([
            'message' => 200,
            'new_question' => $newQuestion,
            'message_text' => 'Vistual Wallet saved correctly'
        ]);
    }

    
    public function removeQuestion( string $id)
    {

        $question = Question::findOrFail($id);
        $anwers = Answer::where('question_id', $id)->delete();

        $question->delete();

        return response()->json([
            'message' => 200,
            'message_text' => 'OK'
        ]);
    }


    public function getFinalReport(Request $request){

        $meetingId = $request->meeting_id;
        $questions = Question::where('meeting_id', $meetingId)->get();

        $finalVotes = array();
       

        foreach ($questions as $question) {
            $totalVotes = Vote::where( 'question_id', $question->id )->count();
           
           $provisional = DB::select("SELECT COUNT(*) AS 'votes',answer_id, a.name, ROUND(SUM(o.total_coefficient),3) as total_coefficient 
                FROM votes v
                INNER JOIN answers a ON a.id = v.answer_id 
                INNER JOIN owners o ON v.owner_id = o.id
                WHERE v.question_id = ? GROUP BY answer_id ORDER BY a.id;", [$question->id]); 
            $resultVotes['question'] = $question->name;

            
            foreach ($provisional as $item ) {
                $woners = DB::select("SELECT o.name, o.total_coefficient, o.building, o.`floor`, o.letter, o.total_coefficient 
                    FROM owners o INNER JOIN votes v ON v.owner_id = o.id 
                    WHERE v.answer_id = ? ORDER BY o.building, o.id;", [$item->answer_id]);
                
                $item->owners = $woners;
                $item->percent = round($item->votes * 100 / $totalVotes, 2);
                
            }
            $resultVotes['result'] = $provisional;
            $finalVotes[] = $resultVotes;      

        }

        return response()->json([
            "message" => 200,
            "final_result" => $finalVotes
        ]);
        
    }
    

    public static function getResultByQuestion( $questionId ){

       
        $resultVotes = DB::select("SELECT COUNT(*) AS 'votes',answer_id, a.name, ROUND(SUM(o.total_coefficient),3) as total_coefficient 
            FROM votes v
            INNER JOIN answers a ON a.id = v.answer_id 
            INNER JOIN owners o ON v.owner_id = o.id
            WHERE v.question_id = ? GROUP BY answer_id ORDER BY a.id;", [$questionId]);       

        $totalVotes = Vote::where('question_id', $questionId)->count();
            
        $ownerByVotes = array();
        $cont = 0;
        foreach ($resultVotes as $result) {
            
            $woners = DB::select("SELECT o.name, o.total_coefficient, o.building, o.`floor`, o.letter, o.total_coefficient 
                                  FROM owners o INNER JOIN votes v ON v.owner_id = o.id 
	                              WHERE v.answer_id = ? ORDER BY o.building, o.id;", [$result->answer_id]);
            $stdProvisional = new stdClass();
            $stdProvisional->vote = $result->name;
            $stdProvisional->total = round($result->total_coefficient,3);
            $stdProvisional->owners = $woners;
            $ownerByVotes[] = $stdProvisional;
        }

        foreach ($resultVotes as $result) {
            $result->percent = round($result->votes * 100 / $totalVotes, 2);
        }

        return response()->json([
            "message" => 200,
            "result" => $resultVotes,
            "total_votes" => $totalVotes,
            "result_datails" => $ownerByVotes
        ]);
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
}
