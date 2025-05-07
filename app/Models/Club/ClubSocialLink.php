<?php

namespace App\Models\Club;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClubSocialLink extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'club_id',
        'instagram_link',
        'twiter_link',
        'facebook_link',
        'youtube_link',
        'linkedin_link',
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
}
