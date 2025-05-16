<?php

namespace App\Http\Controllers\Urbanisation;

use App\Http\Controllers\Controller;
use App\Models\Meeting\Answer;
use App\Models\Meeting\Meeting;
use App\Models\Meeting\Vote;
use App\Models\Urbanisation\Owner;
use App\Models\Urbanisation\Property;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class OwnersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $urbanisationId = $request->urbanisation_id;
        $building = $request->building;
        
        $owners = Owner::where('urbanisation_id', $urbanisationId);
        if( strlen($search) > 0){
            $owners->where(DB::raw("CONCAT(owners.name)") , 'like', '%'.$search.'%');
        }

        if( strlen($building) > 0){
            $owners->where('building', $building);
        }

        $owners = $owners->orderBy('building', 'asc')//->orderBy('floor', 'asc')
        ->paginate(18);


        $total = Owner::where('urbanisation_id', $urbanisationId);
        if( strlen($search) > 0){
            $total->where(DB::raw("CONCAT(owners.name)") , 'like', '%'.$search.'%');
        }
        if( strlen($building) > 0){
            $total->where('building', $building);
        }
        $total = $total->count();

        /*
            SELECT * FROM owners o 
            LEFT JOIN owner_meeting om ON o.id = om.owner_id AND om.meeting_id = 1 AND om.deleted_at IS null 
            WHERE o.urbanisation_id = 1 ;
        */
                     
        return response()->json([
            "total" => $total,
            "owners" => $owners
        ]);
    }

     public function getAssistans(Request $request){
        
        $search = $request->search;
        $urbanisationId = $request->urbanisation_id;
        $building = $request->building;
        $meetingId = $request->meeting_id;

       

         $owners = Owner::select("owners.*", "owner_meeting.id as assistId", "owner_meeting.represented_by")
            ->leftJoin("owner_meeting", function($join) use($meetingId){
                $join->on('owners.id', '=', 'owner_meeting.owner_id')
                    ->where('owner_meeting.meeting_id', '=', $meetingId)
                    ->where('owner_meeting.deleted_at', NULL);
            })
            ->where('urbanisation_id', $urbanisationId);

            if( strlen($search) > 0){
                $owners->where(DB::raw("CONCAT(owners.name)") , 'like', '%'.$search.'%');
            }

            if( strlen($building) > 0){
                $owners->where('building', $building);
            }
        $owners = $owners->orderBy('building', 'asc')//->orderBy('floor', 'asc')
        ->paginate(18);

        $total = Owner::where('urbanisation_id', $urbanisationId);
        if( strlen($search) > 0){
            $total->where(DB::raw("CONCAT(owners.name)") , 'like', '%'.$search.'%');
        }
        if( strlen($building) > 0){
            $total->where('building', $building);
        }
        $total = $total->count();
                     
        return response()->json([
            "total" => $total,
            "owners" => $owners
        ]);
     }


    public function getOwnerByBuilding(Request $request){

        $building = $request->building;
        $urbanisationId = $request->urbanisation_id;
        $meetingId = 3;

        $owners = Owner::select("owners.*", "owner_meeting.id as assistId")
            ->join("owner_meeting", function($join) use($meetingId){
                $join->on('owners.id', '=', 'owner_meeting.owner_id')
                    ->where('owner_meeting.meeting_id', '=', $meetingId)
                    ->where('owner_meeting.deleted_at', NULL);
            })
            ->where('urbanisation_id', $urbanisationId);

            if( strlen($building) > 0){
                $owners->where('building', $building);
            }
        $owners = $owners->orderBy('building', 'asc')->get();
       

      /*  $owners = Owner::where('urbanisation_id', $urbanisationId);
        
        if( strlen($building) > 0){
            $owners->where('building', $building);
        }

        $owners = $owners->orderBy('building', 'asc')->get();

        */

        return response()->json([
            "message" => 200,
            "total" => count($owners),
            "owners" => $owners
        ]);
    }


    public function getVotesByQuestion(Request $request){

        $questionId = $request->question_id;

        $votes = Vote::where('question_id', $questionId);
        $votes = $votes->orderBy('id', 'desc')->get();

        return response()->json([
            "message" => 200,
            "votes" => $votes
        ]);
    }
    
    public static function getResultByQuestion(Request $request){

        $questionId = $request->question_id;

        $resultVotes = DB::select("SELECT COUNT(*) AS 'votes',answer_id, a.name, ROUND(SUM(o.total_coefficient),3) as total_coefficient 
            FROM votes v
            INNER JOIN answers a ON a.id = v.answer_id 
            INNER JOIN owners o ON v.owner_id = o.id
            WHERE v.question_id = ? GROUP BY answer_id ORDER BY a.id;", [$questionId]);       

        $totalVotes = Vote::where('question_id', $questionId)->count();
            
        $ownerByVotes = array();
        $cont = 0;

        $answers = Answer::where("question_id", $questionId)->get();

        foreach ($answers as $result) {
            
            $woners = DB::select("SELECT o.name, o.total_coefficient, o.building, o.`floor`, o.letter, o.total_coefficient, om.represented_by   
                                  FROM owners o INNER JOIN votes v ON v.owner_id = o.id 
                                  LEFT JOIN owner_meeting om ON o.id = om.owner_id AND om.deleted_at IS NULL 
	                              WHERE v.answer_id = ? AND v.deleted_at IS null ORDER BY o.building, o.id;", [$result->id]);
            $stdProvisional = new stdClass();
            $stdProvisional->vote = $result->name;
            //$stdProvisional->total = round($result->total_coefficient,3);
            $stdProvisional->owners = $woners;
            $ownerByVotes[] = $stdProvisional;
        }

        foreach ($resultVotes as $result) {
            $result->percent = round($result->votes * 100 / $totalVotes, 2);
        }

        foreach ($ownerByVotes as $result) {
            $totalCoefficient = 0;
            foreach ($result->owners as $owner) {
                $totalCoefficient += $owner->total_coefficient;
            }
            $result->total_coefficient = $totalCoefficient;
        }

        return response()->json([
            "message" => 200,
            "result" => $resultVotes,
            "total_votes" => $totalVotes,
            "result_datails" => $ownerByVotes
        ]);
    }
    

    public function addProperty( Request $request)
    {

        $validator = Validator::make($request->all(),[
            'owner_id' => 'required',
            'name' => 'required|max:191',
            'coefficient' => 'required|between:0,1'
        ]);


        $owner = Owner::findOrFail($request->owner_id);

        $newProperty = Property::create($request->all());
        if($newProperty){
            $owner->addProperty($request->coefficient);
        }
        

        return response()->json([
            'message' => 200,
            'total_coefficient' =>  round($owner->total_coefficient,4),
            'new_property' => $newProperty,
            'message_text' => 'Vistual Wallet saved correctly'
        ]);
    }
    
    
    public function storeVotes( Request $request)
    {


        $meeting = Meeting::findOrFail($request->meeting_id);
        $wonersBuiding = Owner::where( 'urbanisation_id', $meeting->urbanisation_id)
            ->where('building', $request->building)->get(['id']);

        DB::table('votes')->whereIn('owner_id', $wonersBuiding)
            ->where('question_id', $request->question_id)->delete();
        

        $votes = json_decode($request->votes, 1);
        foreach ($votes as $key => $vote) {
            $wonerId = $vote['owner_id'];
            $questionID = $vote['question_id'];
            $answueId = $vote['answer_id'];
            $vote = Vote::create([
                'owner_id' => $wonerId,
                'question_id' => $questionID,
                'answer_id' => $answueId
            ]);
        }


        return response()->json([
            'message' => 200,
            'message_text' => 'Votes saved correctly'
        ]);
    }
    
    
    public function removeProperty( string $id)
    {

        $property = Property::findOrFail($id);
        $owner = Owner::findOrFail( $property->owner_id);

        $owner->removeProperty($property->coefficient);
        $property->delete();

        return response()->json([
            'message' => 200,
            'current_total_coefficient' => round($owner->total_coefficient, 4),
            'message_text' => 'OK'
        ]);
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
        $owner = Owner::findOrFail($id);

        $owner['properties'] = $owner->properties;
        $owner['urbanisation'] = $owner->urbanisation;

        return response()->json( [
            'response' => 200,
            'owner' => $owner
        ]);

    }

    public static function config( ){

        $building = DB::table('owners')
                  ->distinct()
                  ->get(['building']);


         return response()->json([
             'message' => 200,
             'building' => $building,
         ]);
     }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $owner = Owner::findOrFail($id);
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'building' => 'required|max:50', //|digits:9',
            'floor' => 'required|max:191',
            'letter' => 'required|max:191'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }


        $validOwner = Owner::where(function ($query) use($id){
                            $query->where('id', '<>', $id);
                        })->where(function ($query) use($request){
                            $query->where('name', $request->name);
                        })->first();

        if($validOwner){
            $errors[] = 'Ya existe un propietario con ese nombre y apellido ';
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }   
        

        $owner->update($request->all());


        return response()->json([
            'message' => 200,
            'message_text' => 'Propietario actualizado correctamente'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
