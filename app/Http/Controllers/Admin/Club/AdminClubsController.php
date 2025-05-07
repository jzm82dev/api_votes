<?php

namespace App\Http\Controllers\Admin\Club;

use App\Http\Controllers\Club\PublicDataController;
use App\Models\User;
use App\Mail\SendEmail;
use App\Models\Club\Club;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Club\ClubAdditionalInformation;

class AdminClubsController extends Controller
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
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'mobile' => 'required|max:50',
            'email' => 'email|required|max:191',
            'password' => 'required|max:191',
          //  'code' => 'required|max:50',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       /*  if( $request->code != '2025'){
            return response()->json([
                'message' => 403,
                'message_text' => 'Código de registro incorrecto'
            ]);
         }
        */
        
        // SAVE DATA USER
        $validUser = User::where('email', $request->email)->orWhere('mobile', 'like', '%'.$request->mobile.'%')->first();
        
        if($validUser){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un club con este email o teléfono'
            ]);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $hash = str()->random(5);

        $newClub = Club::create(['name' => $request->name, 'email' => $request->email, 'mobile' => $request->mobile, 'hash' => $hash]);
        $additionalInformation = ClubAdditionalInformation::create(['club_id' => $newClub->id]);

        $request->request->add(['club_id' => $newClub->id]);
        $user = User::create($request->all());
        $role = Role::where( 'name', 'Admin-Club')->first();
        $user->assignRole($role);

      //  Mail::to($request->email)->queue( new SendEmail('welcome_message', 'Welcome'));
        PublicDataController::sendEmailVerifyClub($request->email);
       
        return response()->json([
            'message' => 200,
            'monitor' => $user,
            'message_text' => 'Club saved correctly'
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
        //
    }
}
