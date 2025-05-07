<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Subscription\Plan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Webhook;
use App\Models\Subscription\Subscription;

class PaypalWebhookController extends Controller
{

    const EVENT_SUSBCRIPTION_CANCELLED = 'BILLING.SUBSCRIPTION.CANCELLED';
    const EVENT_SUSBCRIPTION_UPDATED = 'BILLING.SUBSCRIPTION.UPDATED';
    const EVENT_SUSBCRIPTION_COMPLETE = 'PAYMENT.SALE.COMPLETED';

    public function index( Request $request)
    {
        $payload = file_get_contents("php://input");


        if (!$payload) {
            http_response_code(400);
            exit("No hay datos recibidos.");
        }
        
        $data = json_decode($payload, true);

        if( self::treatedWebhook($data['id']) ){
            http_response_code(400);
            exit("Webhook has been treated");
        }

        $webhook = Webhook::create([
            'webhook_id' => $data['id'],
            'data' => json_encode($data)
        ]);
        
        if($data['event_type'] === self::EVENT_SUSBCRIPTION_CANCELLED){
            self::eventSubscriptionCancelled($data['resource']);
        }    
        
        //if($data['event_type'] === self::EVENT_SUSBCRIPTION_UPDATED){
        if($data['event_type'] === self::EVENT_SUSBCRIPTION_COMPLETE){ 
            self::eventSubscriptionUpdate($data['resource']);
        }  

    
    }

    public function eventSubscriptionCancelled( $subscriptionWebhookData )
    {
     
        $subscription = Subscription::where('subscription_id', $subscriptionWebhookData['id'])->where('renewal', '1')->first(); 
        if( $subscription ){
            $subscription->cancel();
        }
    }


    public function eventSubscriptionUpdate( $subscriptionWebhookData ){

        $subscription = Subscription::where('subscription_id', $subscriptionWebhookData['billing_agreement_id'])->where('renewal', '1')->first(); 
        if( $subscription ){
            $plan = Plan::findOrFail($subscription->plan_id);
            $dt = Carbon::parse($subscription->active_until);
            $subscription->update([
                'active_until' => $dt->addDays($plan->duration_in_days)
            ]);
        }

    }

    public function treatedWebhook( $webhookId ){

        return Webhook::where('webhook_id', $webhookId )->exists();

    }

}
