<?php

namespace App\Services;

use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use App\Traits\ConsumesExternalServices;

class PaypalService{

    use ConsumesExternalServices;

    protected $baseUri;

    protected $clientId;

    protected $secretId;

    protected $plans;

    public function __construct() {
        $this->baseUri = config('services.paypal.base_uri');
        $this->clientId = config('services.paypal.client_id');
        $this->secretId = config('services.paypal.client_secret');
        $this->plans = config('services.paypal.plans');
    }

    
    public function resolveAuthorization(&$queryParamas, &$formParams, &$headers){
        
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    
    public function resolveAccessToken(){
        $credentials = base64_encode("{$this->clientId}:{$this->secretId}");

        return "Basic {$credentials}";
    }


    public function decodeResponse($response){
        return json_decode($response);
    }


    public function handlePayment(Request $request){
        
        $order = $this->createOrder($request->value, $request->currency);

        $orderLinks = collect($order->links);

        $approveLink = $orderLinks->where('rel', 'approve')->first();

        session()->put('approvalId', $order->id);

        return redirect($approveLink->href);

    }

    
    public function handleApproval(){

        if( session()->has('approvalId') ){
            $approvalId = session()->get('approvalId');
            $payment = $this->capturePayment($approvalId);
            $name = $payment->payer->name->given_name;
            $payment = $payment->purchase_units[0]->payments->captures[0]->amount;
            $amount = $payment->value;
            $currency = $payment->currency_code;
            
            return redirect()->route('home')
            ->withSuccess(['payment' => "Tahnk yoy {$name}. We recive your  {$amount} {$currency} payment"]);
        }

        return redirect()->route('home')
                ->withErrors('We cannot capture your payment. Please, try again.');

    }


    public function createOrder($value, $currency){
        return $this->makeRequest(
            'POST', 
            '/v2/checkout/orders', [], [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                0 => [
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => round($value * self::resolveFactor($currency))/ self::resolveFactor($currency),
                    ]
                ]
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => route('approval'),
                'cancel_url' => route('cancelled')
            ]
        ], [], $isJAsonRequest = true); 
    }


    public function capturePayment( $approvalId ){
        return $this->makeRequest(
                'POST',
                "/v2/checkout/orders/{$approvalId}/capture",
                [],
                [],
                [
                    'Content-type' => 'application/json'
                ]
                );
    }


    public function resolveFactor( $currency ){
        $zeroDecimalCurrencies = ['JPY'];

        if( in_array(strtoupper($currency), $zeroDecimalCurrencies)){
            return 1;
        }

        return 100;
    }


    // Subscriptions

    public function handleSubscription( Request $request){
        
        $name = $request->user()->name;
        $email = $request->user()->email;
        $subscription = $this->createSubscription($request->plan, $name, $email);

        $subcriptionId = $subscription->id;
        $subscriptionsLinks = collect($subscription->links);

        $approveLink = $subscriptionsLinks->where('rel', 'approve')->first();

        session()->put('subcriptionId', $subcriptionId);

        return redirect($approveLink->href);
    }


    public function createSubscription($planSlug, $name, $email){
        return $this->makeRequest(
            'POST', 
            '/v1/billing/subscriptions', [], [
            'plan_id' => $this->plans[$planSlug],
            'subscriber' => [
                'name' => [
                    'given_name' => $name
                ],
                'email_address' => $email
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => route('subscribe.approval', ['plan' => $planSlug]),
                'cancel_url' => route('subscribe.cancelled')
            ]
        ], [], $isJAsonRequest = true); 
    }


    public function validateSubscription(Request $request){
        if( session()->has('subscriptionId')){
            $subscriptionId = session()->get('subscriptionId');
            session()->forget('subscriptionId');

            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }


    public function cancelSubscription($subscriptionId){
        return $this->makeRequest(
            'POST', 
            "/v1/billing/subscriptions/{$subscriptionId}/cancel", 
            [], 
            [
                'reason' => 'Cancel from application'
            ],
            [], $isJAsonRequest = true); 
    }

    public function getSubscription( $subscriptionId ){
        return $this->makeRequest(
            'GET', 
            "/v1/billing/subscriptions/{$subscriptionId}", 
            [], 
            [],
            [], $isJAsonRequest = true); 
    }

}