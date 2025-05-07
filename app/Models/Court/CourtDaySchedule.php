<?php

namespace App\Models\Court;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourtDaySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_day_id',
        'schedule_id'
    ];


    public function schedule(){
        return $this->hasOne(Schedule::class, 'id', 'schedule_id');
    }
}
