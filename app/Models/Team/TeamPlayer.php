<?php

namespace App\Models\Team;

use Carbon\Carbon;
use App\Models\Team\Team;
use App\Models\Player\Player;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamPlayer extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'player_id',
        'team_id'
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

    public function player(){
        return $this->belongsTo(Player::class);
    }

    public function team(){
        return $this->belongsTo(Team::class);
    }

    
    
}
