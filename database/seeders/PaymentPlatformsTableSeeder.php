<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription\PaymentPlatform;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentPlatformsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentPlatform::create([
            'name' => 'Paypal',
            'image' => 'img/payment-platforms/paypal.jpg'
        ]);
        PaymentPlatform::create([
            'name' => 'Stripe',
            'image' => 'img/payment-platforms/stripe.jpg'
        ]);
    }

    //php artisan db:seed --class=PaymentPlatformsTableSeeder
}
