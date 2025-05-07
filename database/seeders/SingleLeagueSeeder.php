<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Tournament\Tournament;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Models\Appointment\AppointmentAttention;
use App\Models\Category\Category;
use App\Models\Couple\Couple;
use App\Models\Couple\CouplePlayer;
use App\Models\Member\ClubUser;
use App\Models\Player\Player;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SingleLeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = Carbon::create(2023, 5, 28, 0, 0, 0);

        $faker = \Faker\Factory::create();
        

        $categoriesName = ['1ª Masculina', '2ª Masculina', '3ª Masculina', '1ª Femenina', '2ª Femenina', '3ª Femenina', '1ª Mixta', '2ª Mixta', '3ª Mixta'];
        $coupleNumber = [8,16,32];
        for ($i=0; $i <1 ; $i++) { 
           
            for ($j=0; $j <19 ; $j++) { 
                $namePlayer1 = $faker->name();
                $surnamePlayer1 = $faker->lastName();
                $mobilePlayer1 = $faker->phoneNumber();

                $namePlayer2 = $faker->name();
                $surnamePlayer2 = $faker->lastName();
                $mobilePlayer2 = $faker->phoneNumber();

                $player1 = User::create([
                    'name' => $namePlayer1,
                    'surname' => $surnamePlayer1,
                    'mobile' => $mobilePlayer1,
                    'email' => 'seeder_default@example.com',
                    'password' => 'zancada'
                ]);

                ClubUser::create([
                    'club_id' => 1,
                    'user_id' => $player1->id,
                    'name' => $namePlayer1,
                    'surname' => $surnamePlayer1
                ]);

                $couple = Couple::create([
                    'club_id' => 1,
                    'league_id' => 25,
                    'category_id' => 68,
                    'name' => $faker->word()
                ]);
                CouplePlayer::create([
                    'user_id' => $player1->id,
                    'couple_id' => $couple->id
                ]);
                
            }
        }


      
        // php artisan db:seed --class=DoubleLeagueSeeder
    }
}
