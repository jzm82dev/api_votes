<?php

namespace App\Http\Controllers\Admin\Player;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Club\Club;
use Illuminate\Http\Request;
use App\Models\Member\ClubUser;
use Illuminate\Support\Facades\DB;
use App\Models\Couple\CouplePlayer;
use App\Http\Controllers\Controller;
use App\Models\Journey\JourneyMatch;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Models\Tournament\TournamentMatch;
use App\Models\Wallet\VirtualWallet;

class PlayerDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function profile(){

       // $this->authorize('profile', Patient::class);
        $userId = auth("api")->user()->id;

        $cachedRecord = Redis::get('player_profile_#'.$userId);
        $playerData = [];

        if(isset($cachedRecord)) {
            $playerData = json_decode($cachedRecord, FALSE);
        }else{
            $player = User::findOrFail($userId);
            $player['avatar'] = $player->avatar ? env("APP_URL")."storage/".$player->avatar : 'assets/img/user-06.jpg';
           // $totalAppointments = Appointment::where("patient_id", $id)->count();
           // $moneyAppointments = Appointment::where("patient_id", $id)->sum("amount");
           // $totalPendingAppointments = Appointment::where("patient_id", $id)->where("status",1)->count();
           // $pendingAppointments = Appointment::where("patient_id", $id)->where("status",1)->get();
           // $appointments = Appointment::where("patient_id", $id)->get();
    
            $playerData = [
                "message" => 200,
                "player" => $player//PatientResource::make($patient),
                /*"appointments" => $appointments->map(function($item){
                    return [
                        "id" => $item->id,
                        "patient" => [
                            "id" => $item->patient->id,
                            "fullname" => $item->patient->name.' '.$item->patient->surname
                        ],
                        "doctor" => [
                            "id" => $item->doctor->id,
                            "fullname" => $item->doctor->name.' '.$item->doctor->surname
                        ],
                        'date_appointment' => $item->date_appointment, 
                        'date_appointment_format' => Carbon::parse($item->date_appointment)->format("d M Y"),
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$item->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$item->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                        "attention_manage" => $item->appointment_attention ? [
                            'id' => $item->appointment_attention->id,
                            'description' => $item->appointment_attention->description,
                            'recipes' => $item->appointment_attention->recipes ? json_decode($item->appointment_attention->recipes) : [],
                            'created_at' => $item->appointment_attention->created_at->format("Y-m-d h:i:A") 
                        ] : NULL,
                        'amount' => $item->amount, 
                        'status_pay' => $item->status_pay, 
                        'status' => $item->status, 
                    ];
                }),
                "pending_appointments" => AppointmentCollection::make($pendingAppointments),
                "total_appointment" => $totalAppointments,
                "total_pending_appointments" => $totalPendingAppointments,
                "total_money" => $moneyAppointments*/
            ];
    
            Redis::set('player_profile_#'.$userId, json_encode($playerData),'EX', 3600);
        }
        return response()->json($playerData);

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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $userId = auth("api")->user()->id;
        $user = User::findOrFail($userId);

        //$this->authorize('update', $club);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

        if($request->hasFile('imagen')){
            if( $user->avatar){
               $result = Storage::delete($user->avatar);
            }
            $path = Storage::putFile("players", $request->file('imagen'));
           $request->request->add(['avatar' => $path]);
        }

        $cachedRecord = Redis::get('player_profile_#'.$userId);
        if(isset($cachedRecord)) {
            Redis::del('player_profile_#'.$userId);
        }

        if($request->password){
            $request->request->add(['password' => bcrypt($request->password)]);
        }
        
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birthday);
        $request->request->add(["birthday" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $user->update($request->all());

        return response()->json([
            'message' => 200,
            'player' => $user,
        ]);

    }
    

    public function myClubs(){
        
        $userId = auth("api")->user()->id;
        $clubUsers = ClubUser::where('status', 'ACCEPT')->where('user_id', $userId)->get();

        return response()->json([
            "clubs" => $clubUsers->map(function($clubUser){
                return [
                    "club_user_id" => $clubUser->id,
                    "name" => $clubUser->club->name,
                    "date_joined" => $clubUser->created_at,
                    "mobile" => $clubUser->club->mobile,
                    "email" => $clubUser->club->email,
                    "city" => $clubUser->club->additional_information->city ? $clubUser->club->additional_information->city->name : '',
                    "logo" => $clubUser->club->avatar ? env("APP_URL")."storage/". $clubUser->club->avatar : 'assets/img/user-06.jpg'
                ];
            }),
        ]);
    }

    public function otherClubs(){

        $userId = auth("api")->user()->id;
        $user = User::findOrFail($userId);

        $clubsUser = ClubUser::where('user_id', $userId)->get();

        $idsAccept = array();
        $idsPending = array();
        $idsCancel = array();
        foreach ($clubsUser as $key => $club) {
            if( $club->status == 'ACCEPT'){
                $idsAccept[] = $club->club_id;
            }elseif($club->status == 'PENDING' ){
                $idsPending[] = $club->club_id;
            }elseif($club->status == 'CANCELED' ){
                $idsCancel[] = $club->club_id;
            }
        }

        $otherClubs = Club::whereNotIn('id', $idsAccept)->whereNotNull('club_verified_at')->get();
        $clubs = collect([]);

        foreach ($otherClubs as $club) {
            $status = 'NO_STATUS';
            if( in_array($club->id, $idsPending)){
                $status = 'PENDING';
            }
            if( in_array($club->id, $idsCancel)){
                $status = 'CANCELED';
            }
            $clubs->push([
                'id' => $club->id,
                'name' => $club->name,
                'email' => $club->email,
                'mobile' => $club->mobile,
                'status' => $status,
                'city' => $club->additional_information->city ? $club->additional_information->city->name : ''
            ]); 
        }

        return response()->json([
            "clubs" => $clubs,
            "pending" => $idsPending
        ]);
    }

    public function registerClub(string $clubId){

        $user = auth("api")->user();
        $clubUser = ClubUser::where('club_id', $clubId)->where('user_id', $user->id)->first();

        if(false == $clubUser){
            ClubUser::create([
                'club_id' => $clubId,
                'user_id' => $user->id,
                'status' => 'PENDING',
                'name' => $user->name,
                'surname' => $user->surname
            ]);
        }else{
            $clubUser->update(['status' => 'PENDING']);
        }

        return response()->json([
            'message' => 200
        ]);
    }


    public function cancelRegisterClub(string $clubId){

        $user = auth("api")->user();

        $clubUser = ClubUser::where('club_id', $clubId)->where('user_id', $user->id)->first();
        $clubUser->delete();
        

        return response()->json([
            'message' => 200
        ]);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function deleteClubUser(string $id)
    {
       
        $clubUser = ClubUser::findOrFail($id);
        $clubUser->delete();
        
        return response()->json([
            'message' => 200
        ]);
    }


    public function getUserClub($user_id){
        $user = auth('api')->user();
        $clubId = auth("api")->user()->club_id;
        $member = User::findOrFail($user_id);

        
        $this->authorize('viewMember', $member);

        $memberUser = ClubUser::where("user_id", $member->id)->where('club_id', $clubId)->first();
        
        $member['avatar'] = $member->avatar ? env("APP_URL")."storage/".$member->avatar : 'assets/img/user-06.jpg';
        $member['name'] = $memberUser->name;
        $member['surname'] = $memberUser->surname;
        $member['status'] = $memberUser->status;

        return response()->json( [
            'member' => $member
        ]);
    }

    public function getMatches(){
        
        $user = auth('api')->user();
        $couplesPlayer = CouplePlayer::where('user_id', '=', $user->id)->get();
        
        $leagueMatches = collect([]);
        foreach ($couplesPlayer as $couplePlayer) {
            $leagueMatches = JourneyMatch::where(function ($query) use ($couplePlayer){
                $query->where('local_couple_id', $couplePlayer->couple_id)
                ->orWhere('visiting_couple_id', $couplePlayer->couple_id);
            })->where(function ($query) {
                $query->where('match_finished', '1');
            })->get();
        }

        $tournamentMatches = collect([]);
        foreach ($couplesPlayer as $couplePlayer) {
            $tournamentMatches = TournamentMatch::where(function ($query) use ($couplePlayer){
                $query->where('local_couple_id', $couplePlayer->couple_id)
                ->orWhere('visiting_couple_id', $couplePlayer->couple_id);
            })->where(function ($query) {
                $query->where('match_finished', '1');
            })->get();
        }

            

        return response()->json([
            'message' => 200,
            'couples_player' => $couplesPlayer,
            'league_matches' => $leagueMatches->map(function($item){
                return [
                    "league_name" => $item->journey->league->name,
                    "id" => $item->id,
                    "local_players" => $item->local_couple ? $item->local_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name,
                            "surname" => $player->user->surname
                        ];
                    }) : [],
                    "visiting_players" => $item->visiting_couple ? $item->visiting_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name, 
                            "surname" => $player->user->surname
                        ];
                    }): [], 
                    "result_set_1" => $item->result_set_1,
                    "result_set_2" => $item->result_set_2,
                    "result_set_3" => $item->result_set_3,
                    "match_finisehd" => $item->match_finished
                    
                    
                ];
            }),
            'tournament_matches' => $tournamentMatches->map(function($item){
                return [
                    "tournament_name" => $item->tournament->name,
                    "id" => $item->id,
                    "local_players" => $item->local_couple ? $item->local_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name, 
                            "surname" => $player->user->surname
                        ];
                    }) : [],
                    "visiting_players" => $item->visiting_couple ? $item->visiting_couple->players->map(function($player){
                        return [
                            "id" => $player->user->id,
                            "name" => $player->user->name, 
                            "surname" => $player->user->surname
                        ];
                    }): [], 
                    "result_set_1" => $item->result_set_1,
                    "result_set_2" => $item->result_set_2,
                    "result_set_3" => $item->result_set_3,
                    "match_finisehd" => $item->match_finished
               ];
            }),
        ]);

    }

    public function getWallets(){

        $user = auth('api')->user();

        $wallets = VirtualWallet::where('mobile', $user->mobile)->get();
        
        return response()->json([
            'message' => 200,
            'wallets' => $wallets->map(function($item){
                return [
                    "id" => $item->id,
                    "club_name" => $item->club->name,
                    "amount" => $item->amount,
                    "history" => $item->virtual_wallet_spent
                ];
            })
        ]);
    }
   
    public function acceptClubPlayer(string $club_user_id)
    {
        $userId = auth("api")->user()->id;
        $clubUser = ClubUser::findOrFail($club_user_id);

        $clubUser->update(['status' => 'ACCEPT']);

        return response()->json([
            'message' => 200,
        ]);

    }
    

    public function cancelClubPlayer(string $club_user_id)
    {
        $userId = auth("api")->user()->id;
        $clubUser = ClubUser::findOrFail($club_user_id);

        $clubUser->update(['status' => 'CANCELED']);

        return response()->json([
            'message' => 200,
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
