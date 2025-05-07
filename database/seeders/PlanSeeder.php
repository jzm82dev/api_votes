<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'slug' => 'monthly',
            'price' => 1200, //12.00
            'duration_in_days' => 30
        ]);

        Plan::create([
            'slug' => 'yearly',
            'price' => 9999, //99.99
            'duration_in_days' => 365
        ]);
    }

     //php artisan db:seed --class=PlanSeeder
}
