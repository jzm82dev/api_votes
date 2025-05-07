<?php

namespace App\Services;

use App\Models\Subscription\Payment;
use Illuminate\Http\Request;
use App\Models\Subscription\Plan;
use App\Traits\ConsumesExternalServices;
use App\Models\Subscription\Subscription;

class StripeService{

    use ConsumesExternalServices;

    protected $key;

    protected $baseUri;

    protected $secret;

    protected $plans;

    public function __construct() {
        $this->baseUri = config('services.stripe.base_uri');
        $this->key = config('services.stripe.key');
        $this->secret = config('services.stripe.secret');
        $this->plans = config('services.stripe.plans');
    }

    
    public function resolveAuthorization(&$queryParamas, &$formParams, &$headers){
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    
    public function resolveAccessToken(){
       return "Bearer {$this->secret}";
    }


    public function decodeResponse($response){
        return json_decode($response);
    }


    public function handlePaymentOld(Request $request){

        $request->validate([
            'payment_method' => 'required'
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);
        session()->put('paymentIntentId', $intent->id);
        /*$confirmation = $this->confirmPayment($intent->id);
        return response()->json([
            'message' => 200,
            'intent' => $intent,
        ]);*/
        return redirect()->route('approval');

    }

    public function handlePayment(Request $request){

        $request->validate([
            'payment_method' => 'required'
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);
        session()->put('paymentIntentId', $intent->id);
        $confirmation = $this->confirmPayment($intent->id);
        if($confirmation->status === 'succeeded'){
            return response()->json([
                'message' => 200,
                'intent' => 'All ok',
            ]);
        }
        if($confirmation->status === 'requires_action'){
            $clientSecret = $confirmation->client_secret;
            return response()->json([
                'message' => 200,
                'status' => 'need 3ds',
                'client_secret' => $confirmation->client_secret,
            ]);
            /*return view('stripe.3d-secure')->with([
                'clientSecret' => $clientSecret
            ]);*/

        }
        
        return response()->json([
            'message' => 200,
            'intent' => 'Algo ha pasado',
        ]);
    }


    
    public function handleApproval(){

        if(session()->has('paymentIntentId')){
            
            
            $paymentIntentId = session()->get('paymentIntentId');
            $confirmation = $this->confirmPayment($paymentIntentId);

            if($confirmation->status === 'requires_action'){
                $clientSecret = $confirmation->client_secret;

                return view('stripe.3d-secure')->with([
                    'clientSecret' => $clientSecret
                ]);
            }
            
            if($confirmation->status === 'succeeded'){
                $name = auth()->user()->name;
                $currency = strtoupper($confirmation->currency);
                $amount = $confirmation->amount / $this->resolveFactor($currency);

                return redirect()
                ->route('home')
                ->withSuccess([
                    'payment' => "Thank you,  $name . We received your  $amount $currency  payment"
                ]); 
            }
        }

        return redirect()
                    ->route('home')
                    ->withErrors('We were unable to confirm your payment. Try again, please');
    }


    public function createOrder($value, $currency){
        
    }


    public function capturePayment( $approvalId ){
       
    }


    public function createCustomer($name, $email, $paymentMethod){
        return $this->makeRequest(
            'POST',
            '/v1/customers',
            [],
            [
                'source' => $paymentMethod,
                'email' => $email,
                'name' => $name
            ],
            []

        );
    }

    public function createSubscriptionOld( $customerId, $paymentMethod, $productPriceId){
        return $this->makeRequest(
            'POST',
            '/v1/subscriptions',
            [],
            [
                'customer' => $customerId,
                'items' => [
                    ['price' => $productPriceId]
                ],
                'default_payment_method' => $paymentMethod,
                'expand' => ['latest_invoice.payment_intent']
            ],
            []
        );
    }


    public function createSubscription( $customerId, $paymentMethod, $productPriceId){
        return $this->makeRequest(
            'POST',
            '/v1/subscriptions',
            [],
            [
                'customer' => $customerId,
                //'default_payment_method' => $paymentMethod,
                'items' => [
                    ['price' => $productPriceId]
                ],
               /* 'payment_method_data' => [
                    'type' => 'card',
                    'card' => [
                        'token' => $paymentMethod
                    ]
                ],*/ 
               // 'payment_behavior' => 'default_incomplete', 
               // 'payment_settings' => ['save_default_payment_method' => 'on_subscription'], 
                'expand' => ['latest_invoice.payment_intent'], 
            ],
            []
        );
    }

    public function handleSubscription(Request $request){

        $name = $request->user()->name;
        $email = $request->user()->email;
        $clubId = $request->user()->club_id;

        $customer = $this->createCustomer($name, $email, $request->payment_method);
        
        $subscription = $this->createSubscription($customer->id, $request->payment_method, $this->plans[$request->plan]);
        
        if( $subscription ){
            $plan = Plan::where('slug', $request->plan)->firstOrFail();
            $user = $request->user();
        
            $subs = Subscription::create([
                'active_until' => now()->addDays($plan->duration_in_days + 1),
                'plan_id' => $plan->id,
                'club_id' => $clubId,
                'subscription_id' => $subscription->id
            ]);

            $hash = random_int(10000, 99999);
            $payment = Payment::create([
                'payment_number' => '#WLR-'.$hash,
                'club_id' => $clubId,
                'item' => 'Subscription plan '.$plan->name,
                'description' => 'Monthly payment subscription of WeLoveRacket',
                'amount' => $plan->price,
                'currency' => 'eur',
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'message' => 200,
                'customer' => $customer,
                'subscription' => $subscription
            ]); 
        }else{
            return response()->json([
                'message' => 422,
                'errors_text' => 'We were unable to active your subscription. Try aganin, please'
            ]);
        }
    

    }

    public function validateSubscription(Request $request){
        if( session()->has('subscriptionId')){
             $subscriptionId = session()->get('subscriptionId');
             session()->forget('subscriptionId');

             return $request->subscriptionId == $subscriptionId;
         }

         return false;
     }

    public function createIntent( $amount, $currency, $paymentMethod){
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount' => round($amount * $this->resolveFactor($currency)),
                'currency' => strtolower($currency),
                //'payment_method' => $paymentMethod,
                'payment_method_data' => [
                    'type' => 'card',
                    'card' => [
                        'token' => $paymentMethod
                    ]
                ], 
                'automatic_payment_methods' => [
                    'enabled' => 'true',
                    'allow_redirects' => 'never'
                ],
              //  'confirmation_method' => 'manual'
            ],
            []

        );
    }
    
    public function confirmPayment( $paymentIntentId){
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/{$paymentIntentId}/confirm"
            );
    }


    public function resolveFactor( $currency ){
        $zeroDecimalCurrencies = ['JPY'];

        if( in_array(strtoupper($currency), $zeroDecimalCurrencies)){
            return 1;
        }

        return 100;
    }


    
    public function cancelSubscription($subscriptionId){
        return $this->makeRequest(
            'DELETE',
            "/v1/subscriptions/{$subscriptionId}"
            );
    }


    public function getSubscription( $subscriptionId ){
        return $this->makeRequest(
            'GET', 
            "/v1/subscriptions/{$subscriptionId}/resume", 
            [], 
            [],
            [], $isJAsonRequest = true); 
    }


}