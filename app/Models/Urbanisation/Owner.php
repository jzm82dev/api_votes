<?php

namespace App\Models\Urbanisation;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'urbanisation_id',
        'name',
        'building',
        'floor',
        'letter', 
        'total_coefficient'
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

    public function urbanisation(){
        return $this->belongsTo(Urbanisation::class);
    }

    public function properties(){
        return $this->hasMany(Property::class);
    }

    public function removeProperty($coefficient){
        $this->total_coefficient = $this->total_coefficient - $coefficient;
        $this->save();
    }

    public function addProperty($coefficient){
        $this->total_coefficient = $this->total_coefficient + $coefficient;
        $this->save();
    }


}
