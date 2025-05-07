<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            'usd',
            'eur',
            'gbp',
            'jpy'
        ];

        foreach ($currencies as $currency) {
            Currency::create([
                'iso' => $currency
            ]);
        }
    }

    // //php artisan db:seed --class=CurrenciesTableSeeder
}
