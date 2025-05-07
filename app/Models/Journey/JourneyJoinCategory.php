<?php

namespace App\Models\Journey;

use App\Models\Category\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JourneyJoinCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'journey_id',
        'category_id'
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

    public function journey()
    {
        return $this->belongsTo(Journey::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

   
}
