<?php

namespace App\Http\Controllers\Admin\Member;

use App\Models\User;
use App\Models\Club\Club;
use Illuminate\Http\Request;
use App\Models\Member\ClubUser;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\User\UserColletion;
use Spatie\Permission\PermissionRegistrar;

class MembersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        $this->authorize('viewAnyMember', User::class);

        $clubId = auth("api")->user()->club_id;
        $search = $request->search;
       

        $members = DB::table("users")
                ->where('users.deleted_at', NULL)
                ->where("users.email" , 'like', '%'.$search.'%')
                ->join("club_users","club_users.user_id", "=", "users.id")
                ->where(DB::raw("CONCAT(club_users.name, ' ',club_users.surname)") , 'like', '%'.$search.'%')
                ->where('club_users.club_id', $clubId)
                ->where('club_users.deleted_at', NULL)
                ->get();
        return response()->json([
            "members" => $members
        ]);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('createMember', User::class);
        
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50',
          //  'email' => 'email|required|max:191'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        // SAVE DATA USER
        $validMember = User::where('mobile', 'like', '%'.$request->mobile.'%');
       // if( $request->email && $request->email != '' ){
       //     $validMember->orWhere('email', $request->email);
            $email = 'default@example.com';
        //}
        $validMember = $validMember->first();
        if($validMember){
            $clubId = auth("api")->user()->club_id;
            $existClubMember = ClubUser::where('user_id', $validMember->id)->where('club_id', $clubId)->first();
            if($existClubMember){
                $errors[] = 'Ya existe un socio con este email o teléfono';
                return response()->json([
                    'message' => 422,
                    'errors_text' => $errors
                ]);
            }
            ClubUser::create([
                'club_id' => $clubId,
                'user_id' => $validMember->id,
                'name' => $request->name,
                'surname' => $request->surname
            ]);
            return response()->json([
                'message' => 200,
                'member' => $validMember
            ]);
        }

        if($request->hasFile('imagen')){
            $path = Storage::putFile("members", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        // Password
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomPass = '';
        for ($i = 0; $i < 6; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomPass .= $characters[$index];
        }


        //if($randomPass){
            $request->request->add(['password' => 'player']);//bcrypt($randomPass)]);
        //}

        
        $clubId = auth("api")->user()->club_id;
        $request->request->add(['club_id' => 0]);

        if( $request->email == '' ){
            $request->request->add(['email' => 'default@example.com']);
        }
        
        $user = User::create($request->all());
        
        $role = Role::where("name", "like", "%MEMBER%")->first();
        $user->assignRole($role);

        
        if( $clubId && $user){
            ClubUser::create([
                'club_id' => $clubId,
                'user_id' => $user->id,
                'name' => $request->name,
                'surname' => $request->surname
            ]);
        }
       
        return response()->json([
            'message' => 200,
            'member' => $user,
            'password' => $randomPass,
            'request' => $request->all(),
            'message_text' => 'Monitor saved correctly'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth('api')->user();
        $clubId = auth("api")->user()->club_id;
        $member = User::findOrFail($id);
        
        $this->authorize('viewMember', $member);

        $memberUser = ClubUser::where("user_id", $member->id)->where('club_id', $clubId)->first();
        
        $member['avatar'] = env("APP_URL")."storage/".$member->avatar;
        $member['name'] = $memberUser->name;
        $member['surname'] = $memberUser->surname;

        return response()->json( [
            'member' => $member,
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
        $clubId = auth("api")->user()->club_id;
        $clubUser = ClubUser::where('user_id', $id)->where('club_id', $clubId)->first();

        $member = User::findOrFail($clubUser->user_id);
        $this->authorize('editMember', $member);

        
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        /*$validMember = User::where('mobile', $request->mobile)
                           ->where('id', '<>', $member->id) 
                           ->first();

        if($validMember){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe un usuario con este teléfono'
            ]);
        }*/


        if($request->hasFile('imagen')){
            if( $member->avatar){
               $result = Storage::delete($member->avatar);
            }
            $path = Storage::putFile("members", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
            $member->update(['avatar' => $path]);
        }

       
        

        $clubUser->update([
            'name' => $request->name,
            'surname' => $request->surname
        ]);

        if( $member->name != $request->name || $member->surname != $request->surname ){ 
            DB::table('reservation_info')
                ->join("reservations","reservations.id", "=", "reservation_info.reservation_id")
                ->where('reservations.user_id', $member->id)
                ->update([  
                    'reservation_info.name' => $request->name
                ]);
        }
        
        
        return response()->json([
            'message' => 200,
            'monitor' => $member,
            'message_text' => 'Monitor saved correctly'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $clubId = auth("api")->user()->club_id;
        $clubUser = ClubUser::where('user_id', $id)->where('club_id', $clubId)->first();

        $member = User::findOrFail($clubUser->user_id);
        $this->authorize('deleteMember', $member);
        
        $clubUser->delete();

        return response()->json([
            'message' => 200,
        ]);
        
    }


    public function getPotentialPlayers(Request $data_search){
        
        $clubId = auth("api")->user()->club_id;
        
        $members = DB::table("users")
                ->where('users.deleted_at', NULL)
              //  ->where("users.email" , 'like', '%'.$data_search->name.'%')
              //  ->where("users.mobile" , 'like', '%'.$data_search->mobile.'%')
                ->join("club_users","club_users.user_id", "=", "users.id");
        if( $data_search->name != '' ){
            $members->where("club_users.name" , 'like', '%'.$data_search->name.'%');
        }
        if( $data_search->surname != '' ){
            $members->where("club_users.surname" , 'like', '%'.$data_search->surname.'%');
        }
        if( $data_search->mobile != ''){
            $members->where("users.mobile" , 'like', '%'.$data_search->mobile.'%');
        }
        $members = $members->where('club_users.club_id', $clubId)
            ->where('club_users.deleted_at', NULL)
            ->get();
                
        return response()->json( [
            'message' => 200,
            'members' => $members
        ]);
        
    }
}
