<?php

namespace App\Models\Team;

use App\Models\Category\Category;
use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\League\League;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'category_id',
        'league_id',
        'name',
        'description',
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

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function league(){
        return $this->belongsTo(League::class);
    }

    public function players(){
        return $this->hasMany(TeamPlayer::class);
    }
}
