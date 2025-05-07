<?php

namespace App\Http\Controllers\Admin\Email;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Member\ClubUser;
use App\Models\Club\PotentialClub;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PotentialClubsController extends Controller
{


   /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $this->authorize('viewAnyMember', User::class);

        $clubId = auth("api")->user()->club_id;
        $search = $request->search;
       

        $clubs = DB::table("potential_clubs")
                ->where('potential_clubs.deleted_at', NULL)
                ->where("potential_clubs.email" , 'like', '%'.$search.'%')
                ->where(DB::raw("CONCAT(potential_clubs.name, ' ',potential_clubs.email)") , 'like', '%'.$search.'%')
                ->get();
        return response()->json([
            "clubs" => $clubs
        ]);
        
    }
   /**
    * Store a newly created resource in storage.
    */
   public function store(Request $request)
   {

       $validator = Validator::make($request->all(),[
           'name' => 'required|max:191',
           'mobile' => 'required|max:50',
           'email' => 'email|required|max:191',
       ]);

       if($validator->fails()){
           $errors = get_errors($validator->errors());

           return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

       // SAVE DATA USER
       $validClub = PotentialClub::where('email', $request->email)->orWhere('mobile', 'like', '%'.$request->mobile.'%')->first();
       
       if($validClub){
           return response()->json([
               'message' => 403,
               'message_text' => 'Ya existe un club con este email o teléfono'
           ]);
       }


       $newPotentialClub = PotentialClub::create(['name' => $request->name, 'email' => $request->email, 'mobile' => $request->mobile, 'comment' => $request->comment]);
       
      
       return response()->json([
           'message' => 200,
           'monitor' => $newPotentialClub,
           'message_text' => 'Club saved correctly'
       ]);
       
   }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth('api')->user();
        $clubId = auth("api")->user()->club_id;
        $potentialClub = PotentialClub::findOrFail($id);
    

        return response()->json( [
            'club' => $potentialClub,
            //'user' => $user,
            //'clubs_member' => $member->club_id,
            //'user_club_id' => $user->club_id
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       
        $potentialClub = PotentialClub::findOrFail($id);
       
        
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'email' => 'required|max:191',
            'mobile' => 'required|max:50'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }



       
        

        $potentialClub->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'comment' => $request->comment,
        ]);

       
        
        return response()->json([
            'message' => 200,
            'club' => $potentialClub,
            'message_text' => 'Club saved correctly'
        ]);

    }

}
