<?php

namespace App\Models\Subscription;

use Carbon\Carbon;
use App\Models\Club\Club;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'club_id',
        'item',
        'description',
        'amount',
        'currency',
        'subscription_id',
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


    public function getVisualPriceAttribute(){  //$plan->visual_price

        if( $this->currency == 'eur'){
            return number_format($this->amount / 100, 2, '.', ',').'€';
        }else{
            return '$'.number_format($this->amount / 100, 2, '.', ',');
        }
        
    }


    public function getTaxAttribute(){
        $totalTax = 21 * $this->amount / 100;
        if( $this->currency == 'eur'){
            return number_format($totalTax / 100, 2, '.', ',').'€';
        }else{
            return '$'.number_format($totalTax / 100, 2, '.', ',');
        }  
    }


    public function getSubtotalAttribute(){
        $totalTax = 21 * $this->amount / 100;
        $subTotal = $this->amount - $totalTax;
        if( $this->currency == 'eur'){
            return number_format($subTotal / 100, 2, '.', ',').'€';
        }else{
            return '$'.number_format($subTotal / 100, 2, '.', ',');
        }  
    }

}
