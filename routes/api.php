<?php

//use App\Http\Controllers\Admin\Club\ClubsController;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Team\TeamController;
//use App\Http\Controllers\Player\PlayersController;
use App\Http\Controllers\Club\ClubsController;
use App\Http\Controllers\League\LeaguesController;
use App\Http\Controllers\Admin\Rol\RolesCotrollers;
use App\Http\Controllers\Club\PublicDataController;
use App\Http\Controllers\Journey\JourneyController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Admin\Court\CourtsController;
use App\Http\Controllers\Admin\Staff\StaffsController;
use App\Http\Controllers\Admin\Doctor\DoctorsController;
use App\Http\Controllers\Admin\Member\MembersController;
use App\Http\Controllers\Admin\Player\PlayersController;
use App\Http\Controllers\League\ResultsLeagueController;
use App\Http\Controllers\Payment\SubscriptionController;
use App\Http\Controllers\Admin\Club\AdminClubsController;
use App\Http\Controllers\Admin\Email\EmailController;
use App\Http\Controllers\Admin\Email\PotentialClubsController;
use App\Http\Controllers\Payment\PaypalWebhookController;
use App\Http\Controllers\Payment\StripeWebhookController;
use App\Http\Controllers\Admin\Monitor\MonitorsController;
use App\Http\Controllers\Admin\Patient\PatientsController;
use App\Http\Controllers\Dashboard\DashboardkpoController;
use App\Http\Controllers\Tournament\TournamentsController;
use App\Http\Controllers\Admin\Player\PlayerDataController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\AppointmentPayController;
use App\Http\Controllers\Admin\Reservation\ReservationsController;
use App\Http\Controllers\Admin\Specialities\SpecialitiesController;
use App\Http\Controllers\Admin\Wallet\VirtualWalletsController;
use App\Http\Controllers\Appointment\AppointmentAttentionController;
use App\Http\Controllers\League\CoupleNotPlayHourTournamentController;
use App\Http\Controllers\Meeting\MeetingsController;
use App\Http\Controllers\Meeting\QuestionsController;
use App\Http\Controllers\Tournament\TournamentMatchDateCourtController;
use App\Http\Controllers\Tournament\TournamentScheduleDayHourController;
use App\Http\Controllers\Urbanisation\OwnersController;
use App\Http\Controllers\Urbanisation\UrbanisationsController;
use App\Models\Urbanisation\Urbanisation;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
 
    //'middleware' => 'auth:api',
    'prefix' => 'auth',
    //'middleware' => ['role:writer']
 
], function ($router) {

    // Auth
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/list', [AuthController::class, 'list'])->name('me');
    Route::post('/reg', [AuthController::class, 'reg']);
    // Players
    Route::resource("/players", PlayersController::class);
    // Clubs
    Route::resource("/clubs", AdminClubsController::class);
    
    
});

Route::group([
    'prefix' => 'data',
], function ($router) {
    // Results leagues
    Route::resource("results", ResultsLeagueController::class);
    Route::post("results/category/{id}", [ResultsLeagueController::class, "categoryDetails"])->name('categoryDetails');
    Route::post("results/categories", [ResultsLeagueController::class, "rankingPerCategory"])->name('rankingPerCategory');
    Route::post("results/more-details-category/{id}", [ResultsLeagueController::class, "getMoreDetailsCategory"])->name('getMoreDetailsCategory');
});


Route::group([
    'prefix' => 'public',
], function ($router) {
    // Create pdf
    Route::get('/payments/get-invoice-pdf/{id}', function()
    {
         $pdf = App::make('dompdf.wrapper');
         $pdf->loadHTML('<h1>Test</h1>');
         return $pdf->stream();
    });

    // Send notification whatsapp
    /* Route::get('/whatsapp/demo', function()
    {

        $accessToken = 'EAAFQqJKpYMkBOxNIdWvoqssP99X8EXTiwXNZAZCj3o5mGrRAqBRrogJExb5KW6izK8iNQWL1fhZCzOeve9GFv0wN0PTaRntfk2ihLHvlBkGSSZAngBvqXJEEYatRSFikbOSCurEz9EH5ZBoROFE4ZCXtYoBpO2mUCHSl0YZCZASeV0F4hJ9fNJuR6WO3mCBsotfbix6lif9oY6P5PPbeSrLB2Dz49DiRm87i';   
        $fbApiUrl = 'https://graph.facebook.com/v17.0/265691049954112/messages';
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => '+34626804645',
            'type' => 'template',
            'template' => [
                'name' => 'hello_world',
                'language' => [
                    'code' => 'en_US',
                ],
                "components"=>  [
                    [
                        "type" =>  "header",
                        "parameters"=>  [
                            [
                                "type"=>  "text",
                                "text"=>  "Nueva Marina"
                            ]
                        ]
                    ],
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type"=> "text",
                                "text"=>  "María"
                            ],
                            [
                                "type"=> "text",
                                "text"=>  "WPT"
                            ],
                            [
                                "type"=> "text",
                                "text"=>  "19:00"
                            ],
                            [
                                "type"=> "text",
                                "text"=>  "21:00"
                            ],
                        ] 
                    ],
                ],
            ],
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init($fbApiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        echo "HTTP Code: $httpCode\n";
        echo "Response:\n$response\n";

    });
    */

    Route::resource("club-data", PublicDataController::class);
    Route::post("find-clubs", [PublicDataController::class, "searchClubs"])->name('searchClubs');
    Route::post("club-data/get-info/{hash}", [PublicDataController::class, "show"])->name('show');
    Route::post("config", [PublicDataController::class, "config"]);
    Route::post("create-booking", [PublicDataController::class, "createBooking"])->name('createBooking');
    Route::post("register-user-tournament", [PublicDataController::class, "registerUserTournament"])->name('registerUserTournament');
    Route::post("send-question-email", [PublicDataController::class, "sendQuestionEmail"])->name('sendQuestionEmail');
    Route::post("send-forgot-password-email", [PublicDataController::class, "sendForgotPasswordEmail"])->name('sendForgotPasswordEmail');
    Route::post("user-by-token/{token}", [PublicDataController::class, "getUserByToken"])->name('getUserByToken');
    Route::post("verify-user/{token}", [PublicDataController::class, "getUserToVerifyEmail"])->name('getUserToVerifyEmail');
    Route::post("send-email-verify-club/{email}", [PublicDataController::class, "sendEmailVerifyClub"])->name('sendEmailVerifyClub');
    Route::post("update-password-user", [PublicDataController::class, "updatePasswordUser"])->name('updatePasswordUser');
    Route::post("get-booking/{id}", [PublicDataController::class, "getBooking"])->name('getBooking');
    Route::post("cancel-booking", [PublicDataController::class, "cancelBooking"])->name('cancelBooking');
    Route::post("get-league/{id}", [PublicDataController::class, "getLeague"])->name('getLeague');
    Route::post("get-leagues", [PublicDataController::class, "getLeagues"])->name('getLeagues');
    Route::post("get-tournament/{hash}", [PublicDataController::class, "getTournament"])->name('getTournament');
    Route::post("get-tournaments", [PublicDataController::class, "getTournaments"])->name('getTournaments');
    Route::post("get-category-league/{category_id}", [PublicDataController::class, "getDataCategoryLeague"])->name('getDataCategoryLeague');
    Route::post("get-category-tournament/{category_id}", [PublicDataController::class, "getDataCategoryTournament"])->name('getDataCategoryTournament');
    Route::post("get-couple-results/{couple_id}", [PublicDataController::class, "getCoupleResults"])->name('getCoupleResults');
    Route::post("get-couple/{couple_id}", [PublicDataController::class, "getCouple"])->name('getCouple');
    Route::post("get-matchs-journey/{journey_id}", [PublicDataController::class, "getMatchsJourney"])->name('getMatchsJourney');
    Route::post("get-draw", [PublicDataController::class, "getDraw"])->name('getDraw');

    Route::post("get-urbanizations", [PublicDataController::class, "getUrbanizations"])->name('getUrbanizations');
    Route::post("urbanisation-data/get-info/{hash}", [PublicDataController::class, "show"])->name('show');
    Route::post("urbanisation-data/meeting-info/{id}", [PublicDataController::class, "getMeeting"])->name('getMeeting');
    Route::post("urbanisation-data/final-report/{id}", [PublicDataController::class, "getFinalReport"])->name('getFinalReport');
});


 //Webhooks
 Route::group([
    'prefix' => 'webhooks',
    ], function ($router) {
    
    Route::post('/subscription/paypal', [PaypalWebhookController::class, 'index'])->name('index'); // https://api.weloveracket.com/api/webhooks/subscription/paypal
    Route::post('/subscription/stripe', [StripeWebhookController::class, 'index'])->name('index'); // https://api.weloveracket.com/api/webhooks/subscription/stripe

});

Route::group([
   // 'middleware' => 'auth:api'
], function ($router) {
     // TEST
     Route::get("tournaments/test/{id}", [TournamentsController::class, "crossing_finger"])->name('crossing_finger');
});

Route::group([
    'middleware' => 'auth:api'
], function ($router) {
    Route::resource("roles", RolesCotrollers::class);
    
    // Staffs
    Route::get("staffs/roles", [StaffsController::class, "roles"]);
    Route::get("staffs/specialities", [StaffsController::class, "specialities"]);
    Route::post("staffs/update/{id}", [StaffsController::class, "update"])->name('update');
    Route::resource("staffs", StaffsController::class);
    
    
    // Specialities
    Route::post("specialities", [SpecialitiesController::class, "store"])->name('store');
    Route::get("specialities", [SpecialitiesController::class, "index"])->name('index');
    Route::get("specialities/{id}", [SpecialitiesController::class, "show"])->name('show');
    Route::post("specialities/update/{id}", [SpecialitiesController::class, "update"])->name('update');
    Route::delete("specialities/{id}", [SpecialitiesController::class, "destroy"])->name('destroy');
   
    // Doctors
    Route::get("doctors/config", [DoctorsController::class, "config"]);
    Route::get("doctors/profile/{id}", [DoctorsController::class, "profile"]);
    Route::post("doctors/update_profile/{id}", [DoctorsController::class, "updateProfile"])->name('updateProfile');
    Route::post("doctors/update/{id}", [DoctorsController::class, "update"])->name('update');
    Route::delete("doctors/{id}", [DoctorsController::class, "deleteDoctor"])->name('deleteDoctor');
    Route::resource("doctors", DoctorsController::class);

    // Patients
    Route::get("patients/config", [PatientsController::class, "config"]);
    Route::get("patients/profile/{id}", [PatientsController::class, "profile"]);
    Route::post("patients/update/{id}", [PatientsController::class, "update"])->name('update');
    Route::delete("patients/{id}", [PatientsController::class, "deletePatient"])->name('deletePatient');
    Route::resource("patients", PatientsController::class);

    // Appointments
    Route::get("appointment/config", [AppointmentController::class, "config"])->name("config");
    Route::get("appointment/filter", [AppointmentController::class, "filter"])->name("filter");
    Route::get("appointment/find", [AppointmentController::class, "findPatient"])->name("findPatient");
    //Calendar
    Route::get("appointment/calendar", [AppointmentController::class, "calendar"])->name("calendar"); 
    Route::post("appointment/update/{id}", [AppointmentController::class, "update"])->name('update');
    Route::delete("appointment/{id}", [AppointmentController::class, "deleteAppointment"])->name('deleteAppointment');
    Route::resource("appointment", AppointmentController::class);

    // Appointment Pays
    Route::get("appointment-pay/config", [AppointmentPayController::class, "config"])->name("config");
    Route::post("appointment-pay/update/{id}", [AppointmentPayController::class, "update"])->name('update');
    Route::delete("appointment-pay/{id}", [AppointmentPayController::class, "deleteAppointmentPay"])->name('deleteAppointmentPay');
    Route::resource("appointment-pay", AppointmentPayController::class);
    
    // AppointemntAttention
    Route::post("appointment-attention/update/{id}", [AppointmentAttentionController::class, "store"])->name('store');
    Route::resource("appointment-attention", AppointmentAttentionController::class);

    //Dashboard
    Route::post("dashboard/admin", [DashboardkpoController::class, "dashboard_admin"])->name('dashboard_admin');
    Route::post("dashboard/admin-year", [DashboardkpoController::class, "dashboard_admin_year"])->name('dashboard_admin_year');
    Route::post("dashboard/doctor", [DashboardkpoController::class, "dashboard_doctor"])->name('dashboard_doctor');
    Route::post("dashboard/doctor-year", [DashboardkpoController::class, "dashboard_doctor_year"])->name('dashboard_doctor_year');
    Route::get("dashboard/config", [DashboardkpoController::class, "config"])->name('config');

    //Payments only one pay
    Route::post('/payments/pay', [PaymentController::class, 'pay'])->name('pay');
    Route::post('/payments/cancel-subscription', [PaymentController::class, 'cancelSubscription'])->name('cancelSubscription');
    Route::get('/payments/approval', [PaymentController::class, 'approval'])->name('approval');
    Route::get('/payments/cancelled', [PaymentController::class, 'cancelled'])->name('cancelled');
    Route::get('/payments/get-plans', [PaymentController::class, 'getPlans'])->name('getPlans');


    // Subscriptions
    Route::post('/subscription/store', [SubscriptionController::class, 'store'])->name('store');
    Route::post('/subscription/save-club-subscription', [SubscriptionController::class, 'saveClubSubscription'])->name('saveClubSubscription');
    Route::get('/subscription/current-subscription', [SubscriptionController::class, "currentSubscription"])->name('currentSubscription');
    
    
    // Payments
    Route::resource("payments", PaymentController::class);
    Route::get("payments/get-payment/{id}", [PaymentController::class, "getPayment"])->name('getPayment');
    //Route::get('/payments/get-invoice-pdf/{id}', [PaymentController::class, 'getPdfInvoice'])->name('getPdfInvoice');
  


    // Leagues
    Route::get("leagues/get-all-players", [LeaguesController::class, "getAllPlayers"])->name('getAllPlayers');
    Route::post("leagues/paid-player-tournament/{id}", [LeaguesController::class, "paidPlayerLeague"]);
    Route::post("leagues/unpaid-player-tournament/{id}", [LeaguesController::class, "unpaidPlayerLeague"]);
    Route::get("leagues/config", [LeaguesController::class, "config"])->name('config');
    Route::post("leagues/update/{id}", [LeaguesController::class, "update"])->name('update');
    Route::delete("leagues/{id}", [LeaguesController::class, "destroy"])->name('destroy');
    Route::resource("leagues", LeaguesController::class);
    

    //Member
    Route::post("members/get-potential-members", [MembersController::class, "getPotentialPlayers"])->name('getPotentialPlayers');
    Route::post("members/update/{id}", [MembersController::class, "update"])->name('update');
    Route::delete("monitors/{id}", [MembersController::class, "deleteClubMember"])->name('deleteClubMember');
    Route::resource("members", MembersController::class);
    

    // Emails
    Route::post("emails/send-email", [EmailController::class, "sendEmailMarketing"])->name('sendEmailMarketing');
    Route::resource("potential-clubs", PotentialClubsController::class);
    Route::post("potential-clubs/update/{id}", [PotentialClubsController::class, "update"])->name('update');


    
    // Monitors
    Route::get("monitors/config", [MonitorsController::class, "config"]);
    Route::post("monitors/save-lessons", [MonitorsController::class, "saveLessons"])->name('saveLessons');
    //Route::get("monitors/profile/{id}", [MonitorsController::class, "profile"]);
    //Route::post("monitors/update_profile/{id}", [MonitorsController::class, "updateProfile"])->name('updateProfile');
    Route::post("monitors/update/{id}", [MonitorsController::class, "update"])->name('update');
    Route::delete("monitors/{id}", [MonitorsController::class, "deleteMonitor"])->name('deleteMonitor');
    Route::resource("monitors", MonitorsController::class);


    // Virtual Wallet 
    Route::get("virtual-wallets/remove-spent/{id}", [VirtualWalletsController::class, "removeSpent"])->name('removeSpent');
    Route::post("virtual-wallets/add-recharge", [VirtualWalletsController::class, "addRecharge"])->name('addRecharge');
    Route::post("virtual-wallets/add-spent", [VirtualWalletsController::class, "addSpent"])->name('addSpent');
    Route::post("virtual-wallets/update/{id}", [VirtualWalletsController::class, "update"])->name('update');
    Route::resource("virtual-wallets", VirtualWalletsController::class);


     // TournamentMatchsDate
    Route::get("tournaments-matchs/create-match-date/{id}", [TournamentMatchDateCourtController::class, "inicializeTournamentMatch"])->name('inicializeTournamentMatch');

    //TournamentScheduleDayHourController
    Route::resource("tournament-schedule", TournamentScheduleDayHourController::class);
    Route::post("tournament-schedule/update/{id}", [TournamentScheduleDayHourController::class, "update"])->name('update');

    //CoupleNotPlayHourTournamentController
    Route::resource("couple-tournament-schedule", CoupleNotPlayHourTournamentController::class);

    // Torunaments
    Route::get("tournaments/get-all-matches", [TournamentsController::class, "getAllMatches"])->name('getAllMatches');
    Route::get("tournaments/get-all-players", [TournamentsController::class, "getAllPlayers"])->name('getAllPlayers');
    Route::post("tournaments/paid-player-tournament/{id}", [TournamentsController::class, "paidPlayerTournament"]);
    Route::post("tournaments/unpaid-player-tournament/{id}", [TournamentsController::class, "unpaidPlayerTournament"]);
    Route::post("tournaments/update/{id}", [TournamentsController::class, "update"])->name('update');
    Route::delete("tournaments/{id}", [TournamentsController::class, "destroy"])->name('destroy');
    Route::resource("tournaments", TournamentsController::class);
    Route::post("tournaments/update-schedule-match/{id}", [TournamentsController::class, "updateScheduleMatch"])->name('updateScheduleMatch');
    Route::post("tournaments/save-result/{id}", [TournamentsController::class, "saveResult"])->name('saveResult');
    Route::post("tournaments/save-result-pickleball/{id}", [TournamentsController::class, "saveResultPickleball"])->name('saveResultPickleball'); 
    Route::get("tournaments/get-match-data/{id}", [TournamentsController::class, "getMatchData"])->name('getMatchData');
    Route::get("tournaments/get-draw/{id}/{type}", [TournamentsController::class, "getDraw"])->name('getDraw');
    Route::get("tournaments/check-configure-tournament/{id}", [TournamentsController::class, "checkConfigureTournament"])->name('checkConfigureTournament');
    Route::get("tournaments/configure-tournament/{id}", [TournamentsController::class, "configureTournament"])->name('configureTournament');
    Route::delete("tournaments/delete-draw/{id}", [TournamentsController::class, "deleteDraw"])->name('deleteDraw');
    Route::get("tournaments/define-range-hours/{id}", [TournamentsController::class, "defineAvailableCourtsHours"])->name('defineAvailableCourtsHours');
    Route::get("tournaments/assig-matchs-schedule/{id}", [TournamentsController::class, "assignMatchSchedule"])->name('assignMatchSchedule');
    Route::get("tournaments/not-play-range-hours/{id}", [TournamentsController::class, "assignMatchCoupleWithSchedule"])->name('assignMatchCoupleWithSchedule');
    Route::get("tournaments/schedule-finals/{id}", [TournamentsController::class, "scheduleFinalsMatch"])->name('scheduleFinalsMatch');
    Route::get("tournaments/get-matches-simple-league/{id}", [TournamentsController::class, "getMatchesSimpleLeague"])->name('getMatchesSimpleLeague');
    Route::get("tournaments/clasification/{id}", [TournamentsController::class, "getClasification"])->name('getClasification');
    Route::get("tournaments/config-matches-page/{id}", [TournamentsController::class, "configMatchesPage"])->name('configMatchesPage');
    
    
   

   
    // Clubs
    Route::post("clubs/update-data", [ClubsController::class, "updateData"]);
    Route::post("clubs/update-data-description", [ClubsController::class, "updateDataDescription"]);
    Route::post("clubs/update-additional-data", [ClubsController::class, "updateAdditionalData"]);
    Route::get("clubs/get-states/{id}", [ClubsController::class, "getStates"])->name('getStates');
    Route::get("clubs/get-cities/{id}", [ClubsController::class, "getCities"])->name('getCities');
    Route::get("clubs/config", [ClubsController::class, "config"]);
    Route::get("clubs/pending-members", [ClubsController::class, "pendingMembers"]);
    Route::get("clubs/profile-data", [ClubsController::class, "profileData"]);
    Route::get("clubs/description-data", [ClubsController::class, "descriptionData"]);
    Route::get("clubs/schedule-data", [ClubsController::class, "scheduleData"]);
    Route::post("clubs/update/{id}", [ClubsController::class, "update"])->name('update');
    Route::post("clubs/updateWeekly", [ClubsController::class, "updateWeekly"])->name('updateWeekly');
    Route::post("clubs/saveSpecialDay", [ClubsController::class, "saveSpecialDay"])->name('saveSpecialDay');
    Route::delete("clubs/delete-special-day/{id}", [ClubsController::class, "removeSpecialDay"])->name('removeSpecialDay');
    Route::get("clubs/services", [ClubsController::class, "getServices"]);
    Route::post("clubs/store-services", [ClubsController::class, "storeServices"])->name('storeServices');
    Route::get("clubs/social-link", [ClubsController::class, "getSocialLinks"]);
    Route::post("clubs/store-social-links", [ClubsController::class, "storeSocialLinks"])->name('storeSocialLinks');
    Route::resource("clubs", ClubsController::class);

    // Players
    // Route::post("players/update/{id}", [PlayersController::class, "update"])->name('update');
    // Route::delete("players/{id}", [PlayersController::class, "destroy"])->name('destroy');
    // Route::get("players/config", [PlayersController::class, "config"])->name('config');
    
    Route::get("player-data/get-my-clubs", [PlayerDataController::class, "myClubs"]);
    Route::get("player-data/other-clubs", [PlayerDataController::class, "otherClubs"]);
    Route::post("player-data/get-member-data/{player_id}", [PlayerDataController::class, "getUserClub"]);
    Route::post("player-data/get-matches", [PlayerDataController::class, "getMatches"]);
    Route::post("player-data/get-wallets", [PlayerDataController::class, "getWallets"]);
    Route::post("player-data/register-club/{id}", [PlayerDataController::class, "registerClub"]);
    Route::post("player-data/cancel-register-club/{id}", [PlayerDataController::class, "cancelRegisterClub"]);
    Route::post("player-data/accept-club-user/{id}", [PlayerDataController::class, "acceptClubPlayer"]);
    Route::post("player-data/cancel-club-user/{id}", [PlayerDataController::class, "cancelClubPlayer"]);
    Route::get("player-data/profile", [PlayerDataController::class, "profile"]);
    Route::post("player-data/update", [PlayerDataController::class, "update"]);
    Route::delete("player-data/{id}", [PlayerDataController::class, "deleteClubUser"])->name('deleteClubUser');
    Route::resource("player-data", PlayerDataController::class);

     // Categories
     Route::post("categories/update/{id}", [CategoryController::class, "update"])->name('update');
     Route::delete("categories/{id}", [CategoryController::class, "destroy"])->name('destroy');
     Route::get("categories/config", [CategoryController::class, "config"])->name('config');
     Route::resource("categories", CategoryController::class);
     Route::post("categories/add-couple", [CategoryController::class, "addCouple"])->name('addCouple');
     Route::get("categories/get-couple/{id}", [CategoryController::class, "getCouple"])->name('getCouple');
     Route::get("categories/get-couple-results/{id}", [CategoryController::class, "getCoupleResults"])->name('getCoupleResults');
     Route::post("categories/edit-couple/{id}", [CategoryController::class, "editCouple"])->name('editCouple');
     Route::delete("categories/delete-couple/{id}", [CategoryController::class, "removeCouple"])->name('removeCouple');
     Route::post("categories/get-players-data", [CategoryController::class, "getPlayers"])->name('getPlayers');
     Route::get("categories/get-players-mobile/{mobile}", [CategoryController::class, "getPlayersByMobile"])->name('getPlayersByMobile');
     Route::get("categories/get-players-name/{name}", [CategoryController::class, "getPlayersByName"])->name('getPlayersByName');
     Route::get("categories/get-players-surname/{surname}", [CategoryController::class, "getPlayersBySurnmae"])->name('getPlayersBySurnmae');
     Route::get("categories/get-total-couples/{id}", [CategoryController::class, "getTotalCouples"])->name('getTotalCouples');
     
     

     // Teams
     Route::post("teams/possible-players", [TeamController::class, "getPossiblePlayers"])->name('getPossiblePlayers');
     Route::post("teams/add-players", [TeamController::class, "addPlayers"])->name('addPlayers');
     Route::post("teams/delete-player", [TeamController::class, "deletePlayer"])->name('deletePlayer');
     Route::post("teams/categories", [TeamController::class, "getCategoriesByLeague"])->name('getCategoriesByLeague');
     Route::post("teams/update/{id}", [TeamController::class, "update"])->name('update');
     Route::delete("teams/{id}", [TeamController::class, "destroy"])->name('destroy');
     Route::get("teams/config", [TeamController::class, "config"])->name('config');
     Route::resource("teams", TeamController::class);

     // Journeys
     Route::post("journeys/categories", [JourneyController::class, "getCategories"])->name('getCategories');
     Route::post("journeys/create-game", [JourneyController::class, "createGame"])->name('createGame');
     Route::get("journeys/create-calendar/{id}", [JourneyController::class, "createCalendar"])->name('createCalendar');
     Route::post("journeys/save-game-board", [JourneyController::class, "saveGamesBoard"])->name('saveGamesBoard');
     Route::post("journeys/get-game-category-journey", [JourneyController::class, "getGamesByCategoryJourney"])->name('getGamesByCategoryJourney');
     Route::post("journeys/get-game-items", [JourneyController::class, "getMatchItems"])->name('getMatchItems');
     Route::post("journeys/update/{id}", [JourneyController::class, "update"])->name('update');
     Route::post("journeys/remove_board", [JourneyController::class, "removeBoard"])->name('removeBoard');
     Route::resource("journeys", JourneyController::class);
     Route::get("journeys/get-matchs/{id}", [JourneyController::class, "getMatchs"])->name('getMatchs');
     Route::post("journeys/save-result/{id}", [JourneyController::class, "saveResult"])->name('saveResult');
     Route::post("journeys/edit-data/{id}", [JourneyController::class, "editData"])->name('editData');
     Route::get("journeys/ranking/{id}", [JourneyController::class, "getRanking"])->name('getRanking');
     

     // Courts
     Route::get("courts/config", [CourtsController::class, "config"]);
     Route::post("courts/update/{id}", [CourtsController::class, "update"])->name('update');
     Route::resource("courts", CourtsController::class);
     


     // Reservations
     Route::get("reservations/config", [ReservationsController::class, "config"]);
     Route::post("reservations/get-reservations-month", [ReservationsController::class, "getResumePerMonth"])->name('getResumePerMonth');
     Route::post("reservations/get-reservations-range", [ReservationsController::class, "getResumePerRange"])->name('getResumePerRange');
     Route::get("reservations/get-recurrent-reservation", [ReservationsController::class, "getRecurrentReservation"])->name("getRecurrentReservation");
     Route::get("reservations/list-recurrents", [ReservationsController::class, "listRecurrents"])->name('listRecurrents');
     Route::get("reservations/config-reurrents", [ReservationsController::class, "configRecurrent"])->name('configRecurrent');
     Route::post("reservations/save-reccurrent", [ReservationsController::class, "saveRecurrent"])->name('saveRecurrent');
     Route::delete("reservations/delete-recurrent/{id}", [ReservationsController::class, "deleteRecurrent"])->name('deleteRecurrent');
     Route::post("reservations/update-recurrent-reservation/{id}", [ReservationsController::class, "updateRecurrent"])->name('updateRecurrent');
     Route::resource("reservations", ReservationsController::class);


     // MIBOTO
    
     Route::get("clubs/config", [UrbanisationsController::class, "config"]);
     Route::post("urbanisations/update/{id}", [UrbanisationsController::class, "update"])->name('update');
     Route::get("urbanisations/get-countries", [UrbanisationsController::class, "config"])->name('config');
     Route::resource("urbanisations", UrbanisationsController::class);
     Route::post("urbanisations/update-additional-data", [UrbanisationsController::class, "updateAdditionalData"]);
     Route::get("urbanisations/get-states/{id}", [UrbanisationsController::class, "getStates"])->name('getStates');
     Route::get("urbanisations/get-cities/{id}", [UrbanisationsController::class, "getCities"])->name('getCities');


     Route::post("owners/add-property", [OwnersController::class, "addProperty"])->name('addProperty');
     Route::post("owners/store-votes", [OwnersController::class, "storeVotes"])->name('storeVotes');
     Route::get("owners/remove-property/{id}", [OwnersController::class, "removeProperty"])->name('removeProperty');
     Route::get("owners/config", [OwnersController::class, "config"])->name('config');
     Route::get("owners/assistants", [OwnersController::class, "getAssistans"])->name('getAssistans');
     Route::get("owners/owner-by-building", [OwnersController::class, "getOwnerByBuilding"])->name('getOwnerByBuilding');
     Route::get("owners/votes-by-question", [OwnersController::class, "getVotesByQuestion"])->name('getVotesByQuestion');
     Route::get("owners/result-by-question", [OwnersController::class, "getResultByQuestion"])->name('getVotesByQuestion');
     Route::post("owners/update/{id}", [OwnersController::class, "update"])->name('update');
     Route::resource("owners", OwnersController::class);

     //Route::post("meetings/add-ssistant-meeting/{meeting_id}/{owner_id}", [MeetingsController::class, "addAssistantMeeting"]);
     Route::post("meetings/add-ssistant-meeting", [MeetingsController::class, "addAssistantMeeting"]);
     Route::post("meetings/cancel-ssistant-meeting/{meeting_id}/{owner_id}", [MeetingsController::class, "cancelAssistantMeeting"]);
     Route::post("meetings/add-question", [MeetingsController::class, "addQuestion"])->name('addQuestion');
     Route::get("meetings/remove-question/{id}", [MeetingsController::class, "removeQuestion"])->name('removeQuestion');
     Route::get("meetings/config", [MeetingsController::class, "config"])->name('config');
     Route::get("meetings/final-report", [MeetingsController::class, "getFinalReport"])->name('getFinalReport');
     Route::resource("meetings", MeetingsController::class);

     Route::get("questions/remove-answer/{id}", [QuestionsController::class, "removeAnswer"])->name('removeAnswer');
     Route::post("questions/add-answer", [QuestionsController::class, "addAnswer"])->name('addAnswer');
     Route::resource("questions", QuestionsController::class);


});