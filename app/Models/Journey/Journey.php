<?php

namespace App\Models\Journey;

use Carbon\Carbon;
use App\Models\League\League;
use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journey extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'league_id',
        'category_id',
        'name',
        'description',
        'date',
        'status',
        'cron_executed_at'   
    ];

   
    public function league(){
        return $this->belongsTo(League::class, 'league_id');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

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

    public function matchs(){
        return $this->hasMany(JourneyMatch::class);
    }
}
