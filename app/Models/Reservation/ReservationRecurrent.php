<?php

namespace App\Models\Reservation;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationRecurrent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'name',
        'mobile',
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

    public function reservation(){
        return $this->hasMany(Reservation::class);
    }
    
}
