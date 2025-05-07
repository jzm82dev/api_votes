<?php

namespace Database\Seeders;

use App\Models\Club\Club;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClubsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $club = Club::create([
            'name' => 'Nueva Marina',
            'club_manager' => 'Cristian',
            'email' => 'nuevamarina@gmail.com',
            'mobile' => '65654865'
        ]);

    }
}
