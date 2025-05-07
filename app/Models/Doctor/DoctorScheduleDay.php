<?php

namespace App\Models\Doctor;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorScheduleDay extends Model
{
    use HasFactory;
    //use SoftDeletes;
    protected $fillable = [
        'user_id',
        'day'
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

    // hasMany es relacción 1:N
    public function schedules_hours(){
        return $this->hasMany(DoctorScheduleJoinHour::class);
    }

    public function doctor(){
        return $this->belongsTo(User::class, 'user_id');
    }


}
