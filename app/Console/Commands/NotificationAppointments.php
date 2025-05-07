<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Mail\NotificationAppoint;
use Illuminate\Support\Facades\Mail;
use App\Models\Appointment\Appointment;

class NotificationAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificate to the clients one 1 hour before his appointment by email';

    /**
     * Execute the console command.
     */
   /* public function handle()
    {
        date_default_timezone_set('Europe/Madrid');
        $simulateHour = strtotime("2024-03-04 08:48:00");
        $appointmests = Appointment::whereDate("date_appointment", now()->format("Y-m-d"))
                                    ->where("status", 1)->get();
          
        $nowTimeNumber = $simulateHour;//strtotime(now()->format("Y-m-d h:i:s"));
        $patients = collect([]);
        foreach ($appointmests as $key => $appointment) {
            $hourStart = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start;
            $hourEnd = $appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end;
            $timeHourStartNumber = strtotime( date("Y-m-d").' '.$hourStart );
            // One hour less timeHourStartNumber: 2023-10-25 08:30:00  -> 2023-10-25 07:30:00
            $timeHourStart1hLess = strtotime(Carbon::parse(date("Y-m-d").' '.$hourStart)->subHour());
            $timeHourEnd1hLess = strtotime(Carbon::parse(date("Y-m-d").' '.$hourEnd)->subHour());


            if( $timeHourStart1hLess <= $nowTimeNumber &&  $timeHourEnd1hLess >= $nowTimeNumber ){
                $patients->push([
                    "name" => $appointment->patient->name,
                    "surname" => $appointment->patient->surname,
                    "avatar" => $appointment->patient->avatar,
                    "email" => $appointment->patient->email,
                    "mobile" => $appointment->patient->mobile,
                    "dni" => $appointment->patient->dni,
                    "speciality" => $appointment->specialitie->name,
                    "hour_start_format" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_start)->format("h:i A"),
                    "hour_end_format" => Carbon::parse(date("Y-m-d").' '.$appointment->doctor_schedule_join_hour->doctor_schedule_hour->hour_end)->format("h:i A"),
                ]);
            }
        }
        
        foreach ($patients as $key => $patient) {
            Mail::to($patient["email"])->send( new NotificationAppoint($patient));
        }
    } */

    public function handle()
    {
        date_default_timezone_set('Europe/Madrid');
        $simulateHour = strtotime("2024-03-04 08:48:00");
        
          
        $nowTimeNumber = $simulateHour;//strtotime(now()->format("Y-m-d h:i:s"));
        $patients = collect([]);
        
                $patients->push([
                    "name" => "Pauli",
                    "surname" => "Ortega",
                    "avatar" => "../images/img_avatar.png",
                    "email" => "jorge.zancada.moreno@gmail.com",
                    "mobile" => "679 015 532",
                    "dni" => "8374367M",
                    "speciality" => "Trauma",
                    "hour_start_format" => '',//Carbon::parse(date("Y-m-d").' '.now())->format("h:i A"),
                    "hour_end_format" => ''//Carbon::parse(date("Y-m-d").' '.now())->format("h:i A"),
                ]);
            
        
        
        foreach ($patients as $key => $patient) {
            Mail::to($patient["email"])->send( new NotificationAppoint($patient));
        }
    }
}
