<?php

namespace App\Models\Appointment;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Models\Doctor\Specialities;
use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor\DoctorScheduleJoinHour;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Appointment\AppointmentAttention;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'date_appointment',
        'specialitie_id',
        'doctor_schedule_join_hour_id',
        'user_id',
        'amount',
        'status_pay',
        'status',
        'day_attention'
    ];


    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('Europe/Madrid');
        $this->attributes['created_at'] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('Europe/Madrid');
        $this->attributes['updated_at'] = Carbon::now();
    }

    public function doctor(){
        return $this->belongsTo(User::class, 'doctor_id');
    } 

    public function user(){
        return $this->belongsTo(User::class);
    } 

    public function patient(){
        return $this->belongsTo(Patient::class);
    } 

    public function specialitie(){
        return $this->belongsTo(Specialities::class);
    } 

    public function doctor_schedule_join_hour(){
        return $this->belongsTo(DoctorScheduleJoinHour::class);//->withTrashed();
    } 

    public function payments(){
        return $this->hasMany(AppointmentPay::class);
    }

    public function appointment_attention(){
        return $this->hasOne(AppointmentAttention::class);
    } 

    public function scopefilterAvanced($query, $specialitieId, $name_doctor, $date, $doctor = null){
        
        if($doctor){
            if(Str::contains(Str::upper($doctor->roles->first()->name), 'DOCTOR')){
                $query->where("doctor_id", $doctor->id);
            }
        }
        
        if( $specialitieId ){
            $query->where("specialitie_id", $specialitieId);
        }

        if( $name_doctor ){
            $query->whereHas("doctor", function($q) use($name_doctor){
                $q->where("name", "like", "%".$name_doctor."%")
                  ->orWhere("surname", "like", "%".$name_doctor."%");
            });  
        }

        if( $date ){
            $dateFormat = Carbon::parse($date)->format("Y-m-d");
            $query->whereDate("date_appointment", $dateFormat);
        }

        return $query;
    }

    public function scopefilterAvancedPay( $query, $specialitieId, $doctorName, $patientName, $dateFrom, $dateTo, $doctor = null){
        if($doctor){
            if(Str::contains(Str::upper($doctor->roles->first()->name), 'DOCTOR')){
                $query->where("doctor_id", $doctor->id);
            }
        }
        if( $specialitieId){
            $query->where('specialitie_id', $specialitieId);
        }
        if($doctorName){
            $query->whereHas("doctor", function($q) use ($doctorName){
                $q->where(DB::raw("CONCAT(users.name, ' ',IFNULL(users.surname, ''), ' ',users.email)") , 'like', '%'.$doctorName.'%');
                  //->where("name", "like", "%".$doctorName."%")
                  //->orWhere("surname", "like", "%".$doctorName."%");
            });
        }
        if($patientName){
            $query->whereHas("patient", function($q) use ($patientName){
                $q->where(DB::raw("CONCAT(patients.name, ' ',IFNULL(patients.surname, ''), ' ',patients.email)") , 'like', '%'.$patientName.'%');
                  //->where("name", "like", "%".$patientName."%")
                  //->orWhere("surname", "like", "%".$patientName."%");
            });
        }

        if($dateFrom && $dateTo){
            $query->whereBetween("date_appointment", [Carbon::parse($dateFrom)->format("Y-m-d"), Carbon::parse($dateTo)->format("Y-m-d")]);
        }
/*
        if($dateFrom){
            $query->whereDate("date_appointment", ">=", $dateFrom)
                  ->whereHas("payments", function ($q) use($dateFrom){
                    $q->whereDate("created_at", ">=", $dateFrom);
                  });
        }
        if($dateTo){
            $query->whereDate("date_appointment", "<=", $dateTo)
                  ->whereHas("payments", function ($q) use($dateTo){
                    $q->whereDate("created_at", "<=", $dateTo);
                  });
        }
*/
        return $query;



    }

    
}
