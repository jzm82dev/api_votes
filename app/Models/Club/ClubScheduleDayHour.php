<?php

namespace App\Models\Club;

use Carbon\Carbon;
use App\Models\Court\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClubScheduleDayHour extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_schedule_day_id',
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

    public function scheduleDay(){
        return $this->belongsTo(ClubScheduleDay::class);
    }

    public function opening_time_id(){
        return $this->hasOne(Schedule::class);
    }

    public function closing_time_id(){
        return $this->hasOne(Schedule::class);
    }
}
