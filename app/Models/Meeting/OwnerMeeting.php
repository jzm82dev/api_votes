<?php

namespace App\Models\Meeting;

use App\Models\Urbanisation\Owner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerMeeting extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'owner_id',
        'meeting_id',
        'represented_by'
    ];

    protected $table = "owner_meeting";

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

    public function owner(){
        return $this->belongsTo(Owner::class);
    }

    public function meeeting(){
        return $this->belongsTo(Meeting::class);
    }
}
