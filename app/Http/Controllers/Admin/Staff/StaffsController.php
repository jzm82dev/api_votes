<?php

namespace App\Http\Controllers\Admin\Staff;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\Doctor\Specialities;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserColletion;
use Illuminate\Support\Facades\Validator;

class StaffsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $search = $request->search;
        
        $users = User::where(DB::raw("CONCAT(users.name, ' ',IFNULL(users.surname, ''), ' ',users.email)") , 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     /*->whereHas("roles", function($q){
                        $q->where("name", "not like", "%DOCTOR%");
                     })*/
                     ->get();

        return response()->json([
            "user" => UserColletion::make($users)
        ]);
    }

    public function roles()
    {
        $roles = Role::where('name', 'not like', '%DOCTOR%')->get();

        return response()->json([
            "message" => 200,
            "roles" => $roles
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $this->authorize('create', User::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
            'password' => 'required|max:191',
            'rol' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $validUser = User::where('email', $request->email)->first();

        if($validUser){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un usuario con este email'
            ]);
        }

        $clubId = auth("api")->user()->club_id;
        $request->request->add(['club_id' => $clubId]);

        if($request->hasFile('imagen')){
            $path = Storage::putFile("user", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }
       
        $user = User::create($request->all());
        
        $role = Role::findById( $request->rol);
        $user->assignRole($role);
        
        return response()->json([
            'message' => 200,
            'user' => $user
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('view', User::class);
        $user = User::findOrFail($id);
        
        return response()->json( [
            'user' => UserResource::make($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('update', User::class);
     
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
            'rol' => 'required'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $validUser = User::where('email', $request->email)
                           ->where('id', '<>', $id) 
                           ->first();

        if($validUser){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un usuario con este email'
            ]);
        }

       
        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $user = User::findOrFail($id);
        
        if($request->hasFile('imagen')){
            if( $user->avatar){
               $result = Storage::delete($user->avatar);
            }
            $path = Storage::putFile("staffs", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }
        
       
        $user->update($request->all());

        if( $request->rol != $user->roles()->first()->id){
            $roleOld = Role::findOrFail( $user->roles()->first()->id);
            $user->removeRole($roleOld);

            $roleNew = Role::findOrFail( $request->rol);
            $user->assignRole($roleNew);
        }

        return response()->json([
            'message' => 200,
            'user' => $user, 
            'request' => $request->all(),
            'id_role' => $user->roles()->first()->id
            //'role' => $user->roles()->first()->id
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $this->authorize('delete', User::class);

        $user = User::findOrFail($id);
        if( $user->avatar){
            Storage::delete($user->avatar);
        }
        //$roleOld = Role::findOrFail( $user->roles()->first()->id);
        //$user->removeRole($roleOld);
        
        $user->delete();

        return response()->json([
            'message' => 200
        ]);
    }

    
}
