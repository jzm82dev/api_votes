<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Subscription\Plan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Webhook;
use App\Models\Subscription\Subscription;

class StripeWebhookController extends Controller
{

    const EVENT_SUSBCRIPTION_DELETED = 'customer.subscription.deleted';
    const EVENT_SUSBCRIPTION_UPDATED = 'customer.subscription.updated';


    public function index( Request $request)
    {

        Log::info('Receive Stripe Webhook');

        $payload = file_get_contents("php://input");


        if (!$payload) {
            http_response_code(400);
            exit("No hay datos recibidos.");
        }
        
        $data = json_decode($payload, true);

        
        if( self::treatedWebhook($data['id']) ){
            http_response_code(200);
            exit("Webhook has been treated");
        }

        $webhook = Webhook::create([
            'webhook_id' => $data['id'],
            'data' => json_encode($data)
        ]);
        

        if($data['type'] === self::EVENT_SUSBCRIPTION_UPDATED){
            self::eventSubscriptionUpdate($data['data']);
        }  

        if($data['type'] === self::EVENT_SUSBCRIPTION_DELETED){
            self::eventSubscriptionDelete($data['data']);
        }  

        //http_response_code(200);
        //exit("Webhook has been treated");

    }



    public function eventSubscriptionUpdate( $subscriptionWebhookData ){

        $event_json = $subscriptionWebhookData['object'];
        
        $subscription = Subscription::where('subscription_id', $event_json['id'])->where('renewal', '1')->first(); 
        if( $subscription ){
            $plan = Plan::findOrFail($subscription->plan_id);
            $dt = Carbon::parse($subscription->active_until);
            $subscription->update([
                'active_until' => $dt->addDays($plan->duration_in_days)
            ]);
        }

    }

    public function eventSubscriptionDelete( $subscriptionWebhookData ){

        $event_json = $subscriptionWebhookData['object'];
        
        $subscription = Subscription::where('subscription_id', $event_json['id'])->where('renewal', '1')->first(); 
        if( $subscription ){
            $plan = Plan::findOrFail($subscription->plan_id);
            $dt = Carbon::parse($subscription->active_until);
            $subscription->cancel();
            http_response_code(200);
            exit("Webhook has been treated");
        }

    }

    public function treatedWebhook( $webhookId ){

        return Webhook::where('webhook_id', $webhookId )->exists();

    }

}
