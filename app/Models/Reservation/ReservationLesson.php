<?php

namespace App\Models\Reservation;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Court\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationLesson extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'user_id',
        'sport_type',
        'end_reservation'
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

    public function reservation(){
        return $this->hasMany(Reservation::class);
    }
    
}
