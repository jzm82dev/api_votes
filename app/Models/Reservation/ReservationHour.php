<?php

namespace App\Models\Reservation;

use Carbon\Carbon;
use App\Models\Court\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationHour extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'schedule_id',
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
    
    public function schedule(){
        return $this->belongsTo(Schedule::class);
    }
    
    public function reservation(){
        return $this->belongsTo(Reservation::class);
    }
}
