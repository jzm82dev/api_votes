<?php

namespace App\Http\Controllers\Meeting;

use App\Http\Controllers\Controller;
use App\Models\Meeting\Answer;
use App\Models\Meeting\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $meetingId = $request->meeting_id;
        
        $questions = Question::where('meeting_id', $meetingId);
        $questions = $questions->orderBy('id', 'desc')->get();
       
              
        return response()->json([
            "total" => count($questions),
            "questions" => $questions
        ]);
    }


    public function addAnswer( Request $request)
    {

        $validator = Validator::make($request->all(),[
            'question_id' => 'required',
            'name' => 'required|max:300'
        ]);


        $newAnswer = Answer::create($request->all());
        
    
        return response()->json([
            'message' => 200,
            'new_answer' => $newAnswer,
            'message_text' => 'Answer saved correctly'
        ]);
    }

    public function removeAnswer( string $id)
    {

        $answer = Answer::findOrFail($id);
       
        $answer->delete();

        return response()->json([
            'message' => 200,
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
        $question = Question::findOrFail($id);

        $answers = Answer::where('question_id', $id)->get();
        

        return response()->json( [
            'response' => 200,
            'question' => $question,
            'answers' => $answers,
            'urbanisation_id' => $question->meeting->urbanisation->id
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
