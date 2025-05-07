<?php

namespace App\Models\Reservation;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Club\Club;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationLessonDay extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'reservation_lesson_id',
        'user_id',
        'sport_type',
        'day_id',
        'day_name'
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

    public function club(){
        return $this->belongsTo(Club::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function reservation_lesson(){
        return $this->belongsTo(ReservationLesson::class);
    }

    public function lesson_hours(){
        return $this->hasMany(ReservationLessonDayHour::class);
    }

   
}
