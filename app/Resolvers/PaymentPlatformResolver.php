<?php

namespace App\Resolvers;

use App\Models\Subscription\PaymentPlatform;
use Exception;


class PaymentPlatformResolver{

    protected $paymentPlaforms;

    public function __construct() {

        $this->paymentPlaforms = PaymentPlatform::all();

    }

    public function resolveService( $paymentPlanformId){
        $name = strtolower($this->paymentPlaforms->firstWhere('id', $paymentPlanformId)->name);
        $service = config("services.{$name}.class");

        if($service){
            return resolve($service);
        }

        throw new Exception("The selected payment platform is not in the configuration");

    }

}