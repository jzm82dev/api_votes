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

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = Carbon::create(2023, 5, 28, 0, 0, 0);

        $faker = \Faker\Factory::create();
        /*$tournament = Tournament::create([
            'club_id' => 1,
            'name' => $faker->randomElement(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']),
            'start_date'  => $date->format('Y-m-d H:i:s'),
            'end_date'  => $date->addWeeks(rand(1, 3))->format('Y-m-d H:i:s'),
            'price' => 20,
            'price_member' => 18
        ]);*/

        $categoriesName = ['1ª Masculina', '2ª Masculina', '3ª Masculina', '1ª Femenina', '2ª Femenina', '3ª Femenina', '1ª Mixta', '2ª Mixta', '3ª Mixta'];
        $coupleNumber = [8,16,32];
        for ($i=0; $i <1 ; $i++) { 
            /*$category = Category::create([
                'tournament_id' => $tournament->id,
                'name' => $faker->randomElement($categoriesName),
            ]);*/
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

                $player2 = User::create([
                    'name' => $namePlayer2,
                    'surname' => $surnamePlayer2,
                    'mobile' => $mobilePlayer2,
                    'email' => 'seeder_default@example.com',
                     'password' => 'zancada'
                ]);

                ClubUser::create([
                    'club_id' => 1,
                    'user_id' => $player2->id,
                    'name' => $namePlayer2,
                    'surname' => $surnamePlayer2
                ]);


                /*$player1 = Player::create([
                    'club_id' => 1,
                    'name' => $faker->name(),
                    'surname' => $faker->lastName(),
                    'mobile' => $faker->phoneNumber(),
                    'total_points' => $faker->numberBetween(0, 1500)
                ]);
                $player2 = Player::create([
                    'club_id' => 1,
                    'name' => $faker->name(),
                    'surname' => $faker->lastName(),
                    'mobile' => $faker->phoneNumber(),
                    'total_points' => $faker->numberBetween(0, 1500)
                ]);
                */
                $couple = Couple::create([
                    'club_id' => 1,
                    'tournament_id' => 19,
                    'category_id' => 56,
                    'name' => $faker->word()
                ]);
                CouplePlayer::create([
                    'user_id' => $player1->id,
                    'couple_id' => $couple->id
                ]);
                CouplePlayer::create([
                    'user_id' => $player2->id,
                    'couple_id' => $couple->id
                ]);
            }
        }


        
        /*
        Appointment::factory()->count(1000)->create()->each(function($p) {
            $faker = \Faker\Factory::create();
            if($p->status == 2){
                AppointmentAttention::create([ 
                    "appointment_id" => $p->id,
                    "patient_id" => $p->patient_id,
                    "description" => $faker->text($maxNbChars = 300),
                    "recipes" =>  json_encode([
                        [
                            "name_medical" => $faker->word(),
                            "uso" => $faker->word(),
                        ],
                    ])
                ]);
            }
            if($p->status_pay == 2){
                AppointmentPay::create([
                    "appointment_id" => $p->id,
                    "amount" => 50,
                    "method_payment" => $faker->randomElement(["credit_card","transfer","cash"]),
                ]);
            }else{
                AppointmentPay::create([
                    "appointment_id" => $p->id,
                    "amount" => $p->amount,
                    "method_payment" => $faker->randomElement(["credit_card","transfer","cash"]),
                ]);
            }
        });
        */
        // php artisan db:seed --class=TournamentSeeder
    }
}
