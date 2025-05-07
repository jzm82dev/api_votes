<?php

namespace App\Models\Reservation;

use App\Models\Court\Court;
use Carbon\Carbon;
use App\Models\Court\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Reservation\ReservationLessonDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationLessonDayHour extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'court_id',
        'reservation_lesson_day_id',
        'opening_time',
        'closing_time',
        'opening_time_id',
        'closing_time_id'
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

    public function court(){
        return $this->belongsTo(Court::class);
    }

    public function reservationLessonDay(){
        return $this->belongsTo(ReservationLessonDay::class);
    }

    public function opening_time_id(){
        return $this->hasOne(Schedule::class);
    }

    public function closing_time_id(){
        return $this->hasOne(Schedule::class);
    }
}
