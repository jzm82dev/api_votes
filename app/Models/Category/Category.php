<?php

namespace App\Models\Category;

use App\Models\Couple\Couple;
use App\Models\Journey\Game;
use App\Models\Journey\Journey;
use App\Models\League\League;
use App\Models\Team\Team;
use App\Models\Tournament\Tournament;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'description',
        'match_type',
        'league_id',
        'tournament_id',
        'type',  // type 1 ->round robin, type 2 -> groups + playoff, group 3-> draw + back draw, type 4 -> draw, type 6 -> League 2 legs
        'points_per_win_2_0',
        'points_per_win_2_1',
        'points_per_lost_0_2',
        'points_per_lost_1_2',
        'visualisations'   
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

    public function league(){
        return $this->belongsTo(League::class);
    }

    public function torunament(){
        return $this->belongsTo(Tournament::class);
    }

    public function teams(){
        return $this->hasMany(Team::class);
    }

    public function games(){
        return $this->hasMany(Game::class);
    }

    public function journeys(){
        return $this->hasMany(Journey::class);
    }

    public function couples(){
        return $this->hasMany(Couple::class);
    }

}
