<?php

namespace App\Http\Controllers\Payment;

use Carbon\Carbon;
use App\Models\Club\Club;
use Illuminate\Http\Request;
use App\Services\PaypalService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Subscription\Plan;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Payment;
use App\Models\Subscription\Subscription;
use App\Resolvers\PaymentPlatformResolver;

class PaymentController extends Controller
{
    protected $paymentPlatformResolver;

    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth');

        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }


    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {
        //$this->authorize('viewAny', Payment::class);

        $search = $request->search;
        $clubId = auth("api")->user()->club_id;
        
        $payments = Payment::where('club_id', $clubId)
                     //->where("name" , 'like', '%'.$search.'%')
                     //->orderBy('start_date', 'asc')
                     ->paginate(10);

        return response()->json([
            "total" => $payments->total(),
            "payments" => $payments->map(function($item){
                return [
                    "id" => $item->id,
                    "payment_number" => $item->payment_number,
                    "item" => $item->item,
                    "description" => $item->description,
                    "amount" => $item->amount,
                    "bauty_amount" => $item->visual_price,
                    "currency" => $item->currency,
                    "subscription_id" => $item->subscription_id,
                    "created_at" => $item->created_at->format("Y-m-d h:i:s")
                ];
            })
        ]);
    }


    
    public function getPayment(string $id){

        $payment = Payment::findOrFail($id);
        $club = Club::findOrFail($payment->club_id);
        $subscriptions = Subscription::where('subscription_id', $payment->subscription_id)->first();
        $country = $club->additional_information->country->name ?? '';
        $state =  $club->additional_information->state->name ?? '';
        $city = $club->additional_information->city->name ?? '';

        $dt = Carbon::parse($payment->created_at);

        $payment['valid_from'] = $payment->created_at;
        $payment['valid_until'] = $dt->addDays($subscriptions->plan->duration_in_days);
        $payment['total_amount'] = $payment->visual_price;
        $payment['tax'] = $payment->tax;
        $payment['subtotal'] = $payment->subtotal;

        $clubData = [
            'name' => $club->name,
            'mobile' => $club->mobile,
            'cif' => $club->cif,
            'address' => $club->additional_information->address,
            'postal_code' => $club->additional_information->postal_code,
            'country' => $country,
            'state' => $state,
            'city' => $city,
        ];

        
        return response()->json([
            'message' => 200,
            'payment' => $payment,
            'club' => $clubData,
        ]);

    }


    public function getPdfInvoice($id){
        //$pdf = App::make('dompdf.wrapper');
        //$pdf->loadHTML('<h1>Test</h1>');
        //return $pdf->stream();
        //return Pdf::loadFile(public_path().'test/invoice.html')->save('/path-to/my_stored_file.pdf')->stream('download.pdf');
        $studentData = [];
        return view('list_students_pdf', compact('studentData'));
    }


    public function pay(Request $request){
        $rules = [
            'value' => ['required', 'numeric', 'min:5'],
            'currency' => ['required', 'exists:currencies,iso'],
            'payment_platform' => ['required', 'exists:payment_platforms,id']
        ];
        
        $request->validate($rules);

        $paymentPlanform = $this->paymentPlatformResolver->resolveService($request->payment_platform);
        session()->put('paymentPlatformId', $request->payment_platform);

        if($request->user()->hasActiveSubscription()){
            $request['value'] = round( $request->value * 0.9, 2);
        }

        //$paymentPlanform = resolve(PaypalService::class);
        
        return $paymentPlanform->handlePayment($request);

    }
    

    public function cancelSubscription(Request $request){

        $paymentPlanform = $this->paymentPlatformResolver->resolveService($request->payment_platform);
        $subscriptionCancelled = $paymentPlanform->cancelSubscription($request->subscription_id);

        if( $request->payment_platform == '1'){
            $subscriptionCancelled = $paymentPlanform->getSubscription($request->subscription_id);
        }

        if( $subscriptionCancelled->status == 'CANCELLED' || $subscriptionCancelled->status == 'canceled'){
            $subcription = Subscription::where('subscription_id', $request->subscription_id)->firstOrFail();
            $subcription->cancel();
            /*$subcription->update([
                'renewal' => '0'
            ]);*/
        }

        return response()->json([
            "message" => 200,
            "subscription_cancelled" => $subscriptionCancelled
        ]);

    }


    public function getPlans(){
        
        $plans = Plan::where('slug', 'basic')->orWhere('slug', 'premium')->get();
        
        return response()->json([
            "message" => 200,
            "plans" => $plans
        ]);
    }

    public function approval(){
        return response()->json([
            'message' => 200,
            'paymentPlatformId' => session()->get('paymentPlatformId'),
        ]); 
        if( session()->has('paymentPlatformId')){
            $paymentPlanformId = session()->get('paymentPlatformId');
            $paymentPlanform = $this->paymentPlatformResolver->resolveService(2);
            //$paymentPlanform = resolve(PaypalService::class);
            
            return $paymentPlanform->handleApproval();
        }

        return redirect()->route('home')
            ->withErrors('We cannot retrieve your payment platform. Try again, please.');

    }

    public function cancelled(){
        return redirect()->route('home')
            ->withErrors('You cancelled the payment');
    }

}
