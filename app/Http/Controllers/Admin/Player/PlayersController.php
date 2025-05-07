<?php

namespace App\Http\Controllers\Admin\Player;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Club\PublicDataController;

class PlayersController extends Controller
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
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50',
            'email' => 'email|required|max:191',
            'password' => 'required|max:191'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        // SAVE DATA USER
        $validPlayer = User::where('email', $request->email)->orWhere('mobile', 'like', '%'.$request->mobile.'%')->first();
        
        if($validPlayer ){
            if($validPlayer->password != 'player' && $validPlayer->password != 'monitor'){
                return response()->json([
                    'message' => 403,
                    'message_text' => 'Ya existe un usuario con este email o teléfono'
                ]);
            }else{
                if($request->password){
                    $request->request->add(['password' => bcrypt($request->password)]);
                }
                if($validPlayer->password == 'player'){
                    $role = Role::where( 'name', 'Player')->first();
                    $request->request->add(['club_id' => 0]);
                    $oldRole = Role::where("name", "like", "%MEMBER%")->first();
                    if( $oldRole ){
                        $validPlayer->removeRole($oldRole);
                    }
                }elseif($validPlayer->password == 'monitor'){
                    $role = Role::where( 'name', 'Monitor')->first();
                }
                
                $validPlayer->update($request->all());
               
                $validPlayer->assignRole($role);
            }
        }else{
            if($request->hasFile('imagen')){
                $path = Storage::putFile("monitors", $request->file('imagen'));
                $request->request->add(['avatar' => $path]);
            }
    
            if($request->password){
                $request->request->add(['password' => bcrypt($request->password)]);
            }
    
            
            $request->request->add(['club_id' => 0]);
    
            $player = User::create($request->all());
            
            $role = Role::where( 'name', 'Player')->first();
            $player->assignRole($role);
    
        }

        PublicDataController::sendEmailVerifyClub($request->email);
        
        return response()->json([
            'message' => 200,
            //'monitor' => $player,
            'message_text' => 'Player saved correctly'
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
