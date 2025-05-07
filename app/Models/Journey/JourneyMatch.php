<?php

namespace App\Models\Journey;

use App\Models\Couple\Couple;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JourneyMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'journey_id',
        'local_couple_id',
        'visiting_couple_id',
        'result_set_1',
        'result_set_2',
        'result_set_3',
        'match_finished'
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

    public function journey(){
        return $this->belongsTo(Journey::class, 'journey_id');
    }

    public function local_couple(){
        return $this->belongsTo(Couple::class, 'local_couple_id');
    }

    public function visiting_couple(){
        return $this->belongsTo(Couple::class, 'visiting_couple_id');
    }

    
}
