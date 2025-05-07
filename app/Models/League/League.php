<?php

namespace App\Models\League;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Journey\Journey;
use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class League extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sport_type',
        'club_id',
        'hash',
        'points_per_win_2_0',
        'points_per_win_2_1',
        'points_per_lost_0_2',
        'points_per_lost_1_2',
        'match_type',
        'price',
        'start_date',
        'avatar',   
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

    public function categories(){
        return $this->hasMany(Category::class)->orderBy('name','asc');;
    }
    
    public function journeys(){
        return $this->hasMany(Journey::class);
    }

    public function club(){
        return $this->belongsTo(Club::class);
    }

    
}
