<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Models\Subscription\Plan;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Payment;
use App\Resolvers\PaymentPlatformResolver;
use App\Models\Subscription\PaymentPlatform;
use App\Models\Subscription\Subscription;

class SubscriptionController extends Controller
{
   
    protected $paymentPlatformResolver;

    public function __construct( PaymentPlatformResolver $paymentPlatformResolver )
    {
        $this->middleware(['auth'/*, 'unsubscribe'*/]);
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }


    public function show(){
        $paymentPlatforms = PaymentPlatform::where('subscriptions_enabled', true)->get();

        return view('subscribe')->with([
                'plans' => Plan::all(),
                'paymentPlatforms' => $paymentPlatforms,
            ]
        );
    }


    public function store( Request $request){

        $rules = [
            'plan' => ['required', 'exists:plans,slug'],
            'payment_platform' => ['required', 'exists:payment_platforms,id'],
        ];

        $request->validate($rules);
        $paymentPlatform =  $this->paymentPlatformResolver->resolveService($request->payment_platform);

        session()->put('subscriptionPlanformId', $request->payment_platform);
        
        return $paymentPlatform->handleSubscription($request); 
    }

    public function saveClubSubscription( Request $request){

        $plan = Plan::where('paypal_id', $request->product_id)->firstOrFail();

        $user = $request->user();
        $clubId = $request->user()->club_id;

        $subscription = Subscription::create([
            'active_until' => now()->addDays($plan->duration_in_days),
            'plan_id' => $plan->id,
            'club_id' => $clubId,
            'subscription_id' => $request->subscription_id
        ]);

        $hash = random_int(10000, 99999);
            $payment = Payment::create([
                'payment_number' => '#WLR-'.$hash,
                'club_id' => $clubId,
                'item' => 'Subscription plan '.$plan->name,
                'description' => 'Monthly payment subscription of WeLoveRacket',
                'amount' => $plan->price,
                'currency' => 'eur',
                'subscription_id' => $request->subscription_id
            ]);
        
        return response()->json([
            "message" => 200,
            "subscription" => $subscription
        ]);
    }

    public function currentSubscription( Request $request){

        $clubId = auth("api")->user()->club_id;
        $isActiveSubscription = false;
        $currentSubscription = Subscription::where('club_id', $clubId)
                        ->where('renewal', 1)->first();
        if($currentSubscription){
            $currentSubscription['slug'] = $currentSubscription->plan->slug;
            if($currentSubscription->isActive()){
                $isActiveSubscription = true;
            }
        }

        return response()->json([
            "message" => 200,
            "current_subscription" => $currentSubscription,
            "has_active_subscription" => $isActiveSubscription
        ]);
    }

    
    

    public function approval(Request $request){
        
        $rules = [
            'plan' => ['required', 'exists:plans,slug']
        ];

        $request->validate($rules);

        if(session()->has('subscriptionPlanformId')){
            $paymentPlatform = $this->paymentPlatformResolver->resolveService(session()->get('subscriptionPlanformId'));
            if($paymentPlatform->validateSubscription($request)){
                $plan = Plan::where('slug', $request->plan)->firstOrFail();
                $user = $request->user();
                $clubId = $request->user()->club_id;

                $subscription = Subscription::create([
                    'active_until' => now()->addDays($plan->duration_in_days),
                    'plan_id' => $plan->id,
                    'club_id' => $clubId,
                    'subscription_id' => session()->get('subcriptionId')
                ]);

                $hash = random_int(10000, 99999);
                $payment = Payment::create([
                    'payment_number' => '#WLR-'.$hash,
                    'club_id' => $clubId,
                    'item' => 'Subscription plan '.$plan->name,
                    'description' => 'Monthly payment subscription of WeLoveRacket',
                    'amount' => $plan->price,
                    'currency' => 'eur',
                    'subscription_id' => $request->subscription_id
                ]);
        
                return redirect()
                        ->route('home')
                        ->withSuccess(['payment' => "Thanks,  $user->name . You have a  $plan->slug subscription. Start using it."]);
            }
    
        }

        return redirect()
            ->route('subscribe.show')
            ->withErrors('We cannot check your subscription. Tray it again please.');

    }

    public function cancelled(){
        return redirect()
            ->route('subscribe.show')
            ->withErrors('You cancelled. Comeback whenever you are ready.');
        

    }
}
