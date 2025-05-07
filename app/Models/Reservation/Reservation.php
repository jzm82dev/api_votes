<?php

namespace App\Models\Reservation;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Court\Court;
use App\Models\Court\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'court_id',
        'user_id',
        'created_by_user_id',
        'reservation_recurrent_id',
        'day_week_number',
        'date',
        'type',
        'start_time',
        'end_time',
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

     public function court(){
        return $this->belongsTo(Court::class);
    }

    public function info(){
            return $this->hasOne(ReservationInfo::class);
    }

    public function hours(){
        return $this->hasMany(ReservationHour::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
}

}
