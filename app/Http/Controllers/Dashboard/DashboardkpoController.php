<?php

namespace App\Http\Controllers\Dashboard;

use stdClass;
use Illuminate\Http\Request;
use Termwind\Components\Raw;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Http\Resources\Appointment\AppointmentCollection;
use App\Http\Resources\User\UserColletion;
use App\Models\User;

class DashboardkpoController extends Controller
{

    public function config(){
        
        $doctors = User::whereHas("roles", function($q) {
                                $q->where("name", "like", "%DOCTOR%");
                            })
                        ->orderBy("id", "asc")->get();   
        
        return response()->json([
            'message' => 200,
            'doctors' => $doctors->map(function($user){
                return [
                    "id" => $user->id,
                    "fullname" => $user->name.' '.$user->surname
                ];
            })
        ]);
    }

    public function dashboard_admin(Request $request){

        if(!auth('api')->user()->can('admin_dashboard')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        date_default_timezone_set('Europe/Madrid');
        $now = now();
        $montBeforeNow = now()->subMonth();

        // Current Month Appointments
        $numAppoinetmentCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                ->whereYear('date_appointment', $now->format("Y"))
                                ->whereMonth('date_appointment', $now->format("m"))
                                ->count();        
        // Month before Appointments 
        $numAppoinetmentBeforeMonth = DB::table('appointments')->where('deleted_at', NULL)
                                ->whereYear('date_appointment', $montBeforeNow->format("Y"))
                                ->whereMonth('date_appointment', $montBeforeNow->format("m"))
                                ->count();
        // Percent VS appointments Appointments
        $percentAppointments = 0;
        if( $numAppoinetmentBeforeMonth > 0){
            $percentAppointments = (($numAppoinetmentCurrentMonth - $numAppoinetmentBeforeMonth)/$numAppoinetmentBeforeMonth) * 100;
        }



         // Current Month Patients
        $numPatientsCurrentMonth = DB::table('patients')->where('deleted_at', NULL)
                                 ->whereYear('created_at', $now->format("Y"))
                                 ->whereMonth('created_at', $now->format("m"))
                                 ->count();
         // Month before Appointments
        $numPatientstBeforeMonth = DB::table('patients')->where('deleted_at', NULL)
                                 ->whereYear('created_at', $montBeforeNow->format("Y"))
                                 ->whereMonth('created_at', $montBeforeNow->format("m"))
                                 ->count();
         // Percent VS appointments Appointments
         $percentPatients = 0;
         if( $numPatientstBeforeMonth > 0){
             $percentPatients = (($numPatientsCurrentMonth - $numPatientstBeforeMonth)/$numPatientstBeforeMonth) * 100;
         }


         
         // Current Month Attention
        $numAttentionsCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                 ->whereYear('day_attention', $now->format("Y"))
                                 ->whereMonth('day_attention', $now->format("m"))
                                 ->count();
         // Month before Attention
        $numAttentionstBeforeMonth = DB::table('appointments')->where('deleted_at', NULL)
                                 ->whereYear('day_attention', $montBeforeNow->format("Y"))
                                 ->whereMonth('day_attention', $montBeforeNow->format("m"))
                                 ->count();
         // Percent VS appointments Attention
         $percentAttentions = 0;
         if( $numAttentionstBeforeMonth > 0){
             $percentAttentions = (($numAttentionsCurrentMonth - $numAttentionstBeforeMonth)/$numAttentionstBeforeMonth) * 100;
         }


          // Earnings Total €
        $totalEarningCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                 ->whereYear('created_at', $now->format("Y"))
                                 ->whereMonth('created_at', $now->format("m"))
                                 ->sum('appointments.amount');
         // Month before Earnings Total €
        $totalEarningtBeforeMonth = DB::table('appointments')->where('deleted_at', NULL)
                                 ->whereYear('created_at', $montBeforeNow->format("Y"))
                                 ->whereMonth('created_at', $montBeforeNow->format("m"))
                                 ->sum('appointments.amount');
         // Percent VS appointments Earnings Total €
         $percentEarning = 0;
         if( $totalEarningtBeforeMonth > 0){
             $percentEarning = (($totalEarningCurrentMonth - $totalEarningtBeforeMonth)/$totalEarningtBeforeMonth) * 100;
         }


         // Upcomming Appointments
         $upcomingAppointments = Appointment::whereYear("date_appointment", $now->format("Y"))
                                            ->whereMonth("date_appointment", $now->format("m"))
                                            ->where("status", 1)
                                            ->orderBy("id", "desc")
                                            ->take(5)
                                            ->get();



        return response()->json([
            "message" => 200,
            "upcoming_appointments" => AppointmentCollection::make($upcomingAppointments),
            "total_appopientments_current_month" => $numAppoinetmentCurrentMonth,
            "total_appopientments_month_before" => $numAppoinetmentBeforeMonth,
            "percent_appointments" => round($percentAppointments, 2),
            //
            "total_patients_current_month" => $numPatientsCurrentMonth,
            "total_patients_month_before" => $numPatientstBeforeMonth,
            "percent_patients" => round($percentPatients, 2),
            //
            "total_attentions_current_month" => $numAttentionsCurrentMonth,
            "total_attentions_month_before" => $numAttentionstBeforeMonth,
            "percent_attentions" => round($percentAttentions, 2),
            //
            "total_earning_current_month" => $totalEarningCurrentMonth,
            "total_earning_month_before" => $totalEarningtBeforeMonth,
            "percent_earning" => round($percentEarning, 2),
            //
            
        ]);
    }

    public function dashboard_admin_year( Request $request){
        
        if(!auth('api')->user()->can('admin_dashboard')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        $year = $request->year;


        // Total men and women by year (obtains year by parameter)
        $queryMonthGender = DB::table("appointments")
                        ->where('appointments.deleted_at', NULL)
                        ->whereYear('appointments.date_appointment', $year)
                        ->join("patients","appointments.patient_id", "=", "patients.id")
                        ->select(
                            DB::raw("YEAR(date_appointment) as year"),
                            DB::raw("MONTH(date_appointment) as month"),
                            DB::raw("SUM( CASE WHEN patients.gender = 1 THEN 1 ELSE 0 END) as man"),
                            DB::raw("SUM( CASE WHEN patients.gender = 2 THEN 1 ELSE 0 END) as woman")
                        )->groupBy("year", "month")
                        ->orderBy("year")
                        ->orderBy("month")
                        ->get();

        // Percent patients by specialities
        $patientBySpecialities = DB::table("appointments")
                                    ->where('appointments.deleted_at', NULL)
                                    ->whereYear('appointments.date_appointment', $year)
                                    ->join("specialities","appointments.specialitie_id", "=", "specialities.id")
                                    ->select("specialities.name", DB::raw("COUNT(appointments.specialitie_id) as count"))
                                    ->groupBy("specialities.name")
                                    ->get();

        $patientPercetSpeciality = collect([]);                   
        $totalPAtientSpecialitie =  $patientBySpecialities->sum("count");
        foreach ($patientBySpecialities as $key => $patientBySpecialitie) {
            $percent = round($patientBySpecialitie->count / $totalPAtientSpecialitie * 100, 2);
            $patientPercetSpeciality->push([
                'name' => $patientBySpecialitie->name,
                'percent' => $percent 
            ]);
        }

        // Total earning by months €
        $totalEarningByMonth = DB::table("appointments")
                                 ->where("appointments.deleted_at", NULL)
                                 ->where("status_pay", 1)
                                 ->whereYear("appointments.date_appointment", $year)
                                 ->select(
                                    DB::raw("MONTH(date_appointment) as month"),
                                    DB::raw("YEAR(date_appointment) as year"),
                                    DB::raw("SUM(amount) as total")
                                 )->groupBy("year", "month")
                                 ->orderBy("year")
                                 ->orderBy("month")
                                 ->get();

        return response()->json([
            "message" => 200,
            "total_earnng_by_mont" => $totalEarningByMonth, 
            "patient_percent_by_speciality" => $patientPercetSpeciality, //->sum('percent') = 100, 
            "patient_by_speciality" => $patientBySpecialities, //->sum("count") = 2000,
            "patient_by_gender" => $queryMonthGender
        ]);

    }

    public function dashboard_doctor(Request $request){

        if(!auth('api')->user()->can('doctor_dashboard')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        date_default_timezone_set('Europe/Madrid');
        
        $doctor_id = $request->doctor_id;
        $now = now();
        $montBeforeNow = now()->subMonth();

        $doctorSelected = User::findOrFail($doctor_id);
        $doctorData = [
                "id" => $doctorSelected->id,
                "fullname" => $doctorSelected->name.' '.$doctorSelected->surname,
                "speciality" => $doctorSelected->specialitie->name
            ];
        

  

        $sumTotalAppointmentCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->whereMonth("date_appointment", $now->format("m"))
                                             ->count();

        $sumTotalAppointmentMonthBefore = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->whereYear("date_appointment", $montBeforeNow->format("Y"))
                                             ->whereMonth("date_appointment", $montBeforeNow->format("m"))
                                             ->count();

        $percentAppointments = 0;
        if( $sumTotalAppointmentMonthBefore > 0){
            $percentAppointments = (($sumTotalAppointmentCurrentMonth - $sumTotalAppointmentMonthBefore)/$sumTotalAppointmentMonthBefore) * 100;
        }

        $sumTotalAttendedAppointmentCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status', 2)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->whereMonth("date_appointment", $now->format("m"))
                                             ->count();

        $sumTotalAttendedAppointmentMonthBefore = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status', 2)
                                             ->whereYear("date_appointment", $montBeforeNow->format("Y"))
                                             ->whereMonth("date_appointment", $montBeforeNow->format("m"))
                                             ->count();

        $percentAppointmentsAtended = 0;
        if( $sumTotalAttendedAppointmentMonthBefore > 0){
            $percentAppointmentsAtended = (($sumTotalAttendedAppointmentCurrentMonth - $sumTotalAttendedAppointmentMonthBefore)/$sumTotalAttendedAppointmentMonthBefore) * 100;
        }
    

        $sumTotalEarningPaidAppointmentCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 1)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->whereMonth("date_appointment", $now->format("m"))
                                             ->sum("amount");

        $sumTotalEarningPaidAppointmentMonthBefore = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 1)
                                             ->whereYear("date_appointment", $montBeforeNow->format("Y"))
                                             ->whereMonth("date_appointment", $montBeforeNow->format("m"))
                                             ->sum("amount");

        $percentEarningPaid = 0;
        if( $sumTotalEarningPaidAppointmentCurrentMonth > 0){
            $percentEarningPaid = (($sumTotalAttendedAppointmentCurrentMonth - $sumTotalEarningPaidAppointmentMonthBefore)/$sumTotalEarningPaidAppointmentMonthBefore) * 100;
        }

        $sumTotalEarningPendingAppointmentCurrentMonth = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 2)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->whereMonth("date_appointment", $now->format("m"))
                                             ->sum("amount");

        $sumTotalEarningPendingAppointmentMonthBefore = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 2)
                                             ->whereYear("date_appointment", $montBeforeNow->format("Y"))
                                             ->whereMonth("date_appointment", $montBeforeNow->format("m"))
                                             ->sum("amount");

        $percentEarningPending = 0;
        if( $sumTotalEarningPendingAppointmentMonthBefore > 0){
            $percentEarningPending = (($sumTotalEarningPendingAppointmentCurrentMonth - $sumTotalEarningPendingAppointmentMonthBefore)/$sumTotalEarningPendingAppointmentMonthBefore) * 100;
        }

        $sumTotalEarningPaidPermonthPer = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 2)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->select(
                                                DB::raw("MONTH(date_appointment) as month"),
                                                DB::raw("SUM(amount) as total")
                                             )->groupBy("month")
                                             ->orderBy("month")
                                             ->get();

        $sumTotalEarningPEndingPermonthPer = DB::table('appointments')->where('deleted_at', NULL)
                                             ->where('doctor_id', $doctor_id)
                                             ->where('status_pay', 2)
                                             ->whereYear("date_appointment", $now->format("Y"))
                                             ->select(
                                                DB::raw("MONTH(date_appointment) as month"),
                                                DB::raw("SUM(amount) as total")
                                             )->groupBy("month")
                                             ->orderBy("month")
                                             ->get();

        
        // Upcomming Appointments
        $upcomingAppointments = Appointment::whereYear("date_appointment", $now->format("Y"))
                            ->where('doctor_id', $doctor_id)
                            ->whereMonth("date_appointment", $now->format("m"))
                            ->where("status", 1)
                            ->orderBy("id", "desc")
                            ->take(5)
                            ->get();


        return response()->json([
            "message" => 200,
            "doctor_selected" => $doctorData,
            "total_appopientments_doctor_current_month" => $sumTotalAppointmentCurrentMonth,
            "total_appopientments_doctor_month_before" => $sumTotalAppointmentMonthBefore,
            "total_appoient_doctor_percent" => round($percentAppointments, 2),
            //
            "total_attendend_appopientments_doctor_current_month" => $sumTotalAttendedAppointmentCurrentMonth,
            "total_attendend_appopientments_doctor_month_before" => $sumTotalAttendedAppointmentMonthBefore,
            "total_appoient_atended_doctor_percent" => round($percentAppointmentsAtended,2),
            //
            "total_earning_paid_appointments_doctor_current_month" => $sumTotalEarningPaidAppointmentCurrentMonth,
            "total_earning_paid_appointments_doctor_month_before" => $sumTotalEarningPaidAppointmentMonthBefore,
            "total_earning_paid_percent" => round($percentEarningPaid, 2),
            //
            "total_earning_pending_appointments_doctor_current_month" => $sumTotalEarningPendingAppointmentCurrentMonth,
            "total_earning_pending_appointments_doctor_month_before" => $sumTotalEarningPendingAppointmentMonthBefore,
            "total_earning_pending_percent" => round($percentEarningPending, 2),
            //
            "total_earning_paid_appointments_doctor_per_month" => $sumTotalEarningPaidPermonthPer,
            "total_earning_pending_appointments_doctor_per_month" => $sumTotalEarningPEndingPermonthPer,
            //
            "upcoming_appointment" => AppointmentCollection::make($upcomingAppointments)
        ]);
    }

    public function dashboard_doctor_year( Request $request){
        
        if(!auth('api')->user()->can('doctor_dashboard')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }


        $doctor_id = $request->doctor_id;
        $year = $request->year;

        $totalEarningByMonth = DB::table("appointments")
                                ->where("appointments.deleted_at", NULL)
                                ->where("doctor_id", $doctor_id)
                                ->where("status_pay", 1)
                                ->whereYear("appointments.date_appointment", $year)
                                ->select(
                                    DB::raw("MONTH(date_appointment) as month"),
                                    DB::raw("YEAR(date_appointment) as year"),
                                    DB::raw("SUM(amount) as total")
                                )->groupBy("year", "month")
                                ->orderBy("year")
                                ->orderBy("month")
                                ->get();

        // Total men and women
        $totalMenAndWomen = DB::table("appointments")->where('appointments.deleted_at', NULL)
                            ->where("appointments.doctor_id", $doctor_id)
                            ->where('patients.deleted_at', NULL)
                            ->whereYear("appointments.date_appointment", $year)
                            ->join("patients","appointments.patient_id", "=", "patients.id")
                            ->select(
                                DB::raw("SUM( CASE WHEN patients.gender = 1 THEN 1 ELSE 0 END) as men"),
                                DB::raw("SUM( CASE WHEN patients.gender = 2 THEN 1 ELSE 0 END) as women")
                            )->get();


        // Total appointments by month VS  Total appointments by month (year before)
        $totalAppointmentsByMonthsByYearQuery = DB::table("appointments")->where("deleted_at", NULL)
                            ->where("doctor_id", $doctor_id)
                            ->WhereYear("appointments.date_appointment", $year)
                            ->select(
                                DB::raw("YEAR(date_appointment) as year"),
                                DB::raw("MONTH(date_appointment) as month"),
                                DB::raw( "COUNT(id) as total")
                            )->groupBy("year", "month")
                            ->orderBy("month", "asc")
                            ->get();
        
         if( count($totalAppointmentsByMonthsByYearQuery) <12){
           $totalAppointmentsByMonthsByYearQuery = self::fillArrayMonth($totalAppointmentsByMonthsByYearQuery, $year);
        }


        $totalAppointmentsByMonthsByYearBeforeQuery = DB::table("appointments")->where("deleted_at", NULL)
                            ->where("doctor_id", $doctor_id)
                            ->WhereYear("appointments.date_appointment", ($year -1))
                            ->select(
                                DB::raw("YEAR(date_appointment) as year"),
                                DB::raw("MONTH(date_appointment) as month"),
                                DB::raw( "COUNT(id) as total")
                            )->groupBy("year", "month")
                            ->orderBy("month", "asc")
                            ->get();
       
        if( count($totalAppointmentsByMonthsByYearBeforeQuery) <12){
           $totalAppointmentsByMonthsByYearBeforeQuery = self::fillArrayMonth($totalAppointmentsByMonthsByYearBeforeQuery, $year -1);
        }

        $monthName = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $resultMontsVs = collect([]);
        $month = 1;
        for ($i=0; $i < 12; $i++) { 
            $resultMontsVs->push([
                "month" => $month,
                "mont_name" => $monthName[$i],
                "total_month_before_year" => $totalAppointmentsByMonthsByYearBeforeQuery[$i]->total,
                "total_month_current_year" => $totalAppointmentsByMonthsByYearQuery[$i]->total,
            ]);
            $month ++;
        }
        


        return response()->json([
                    "message" => 200,
                   // "result_vs_months_appointemnt" => $totalAppointmentVsMonths,
                   // "total_appointmets_year" => $totalAppointmentsByMonthsByYearQuery,
                   // "total_appointmets_year_before" => $totalAppointmentsByMonthsByYearBeforeQuery,
                    "vs_months_appointmnets_year_year_before" => $resultMontsVs,
                    "total_earning_doctor_by_month" => $totalEarningByMonth,
                    "total_by_gender" => $totalMenAndWomen
        ]);

    }


    public function fillArrayMonth( $totalAppoinmentsPerMonth, $year ){
    
        $resultArray = collect([]);
        for ($i=1; $i < 13; $i++) { 
            $item = new stdClass();
                $item ->year = $year;
                $item->month = $i;
                $item->total = 0;
            if( count($totalAppoinmentsPerMonth) > 0){
                
                foreach($totalAppoinmentsPerMonth as $struct) {
                    if ($i == $struct->month) {
                        $item = $struct;
                        break;
                    }
                }
            }
            $resultArray->push($item);
        }
        return $resultArray;
    }


}
