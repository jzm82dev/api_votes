<?php

namespace App\Models\Tournament;

use Carbon\Carbon;
use App\Models\Club\Club;
use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'club_id',
        'hash',
        'sport_type',
        'start_date',
        'end_date',
        'price',
        'price_member',
        'time_per_match',
        'draw_generated',
        'date_starts_registration',
        'hour_starts_registration',
        'date_ends_registration',
        'hour_ends_registration',
        'is_draft',
        'avatar'
    ];

    protected $dates = [
        'end_date'
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

    public function categories(){
        return $this->hasMany(Category::class);
    }

    public function schedule(){
        return $this->hasMany(TournamentScheduleDayHour::class)->orderBy('date')->orderBy('opening_time');
    }

    public function isFinisched(){
        $nowDate = Carbon::now();
        $dt = Carbon::parse($this->end_date);
        return $nowDate->gt($dt);
    }

}
