<?php

namespace Database\Seeders;

use App\Models\Court\Schedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        Schedule::create([
                'hour_start' => '06:00:00', 'hour_end' => '06:30:00', 'hour' => '8' ]); 
	Schedule::create([
                'hour_start' => '06:30:00', 'hour_end' => '07:00:00', 'hour' => '8' ]); 
        Schedule::create([
                'hour_start' => '07:00:00', 'hour_end' => '07:30:00', 'hour' => '8' ]); 
	Schedule::create([
                'hour_start' => '07:30:00', 'hour_end' => '08:00:00', 'hour' => '8' ]); 
        Schedule::create([
                'hour_start' => '08:00:00', 'hour_end' => '08:30:00', 'hour' => '8' ]); 
	Schedule::create([
                'hour_start' => '08:30:00', 'hour_end' => '09:00:00', 'hour' => '8' ]); 
	Schedule::create([
                'hour_start' => '09:00:00', 'hour_end' => '09:30:00', 'hour' => '9' ]); 
        Schedule::create([
                'hour_start' => '09:30:00', 'hour_end' => '10:00:00', 'hour' => '9' ]); 
        Schedule::create([
                'hour_start' => '10:00:00', 'hour_end' => '10:30:00', 'hour' => '10' ]); 
        Schedule::create([
                'hour_start' => '10:30:00', 'hour_end' => '11:00:00', 'hour' => '10' ]); 
        Schedule::create([
                'hour_start' => '11:00:00', 'hour_end' => '11:30:00', 'hour' => '11' ]); 
        Schedule::create([
                'hour_start' => '11:30:00', 'hour_end' => '12:00:00', 'hour' => '11' ]); 
        Schedule::create([
                'hour_start' => '12:00:00', 'hour_end' => '12:30:00', 'hour' => '12' ]); 
        Schedule::create([
                'hour_start' => '12:30:00', 'hour_end' => '13:00:00', 'hour' => '12' ]); 
        Schedule::create([
                'hour_start' => '13:00:00', 'hour_end' => '13:30:00', 'hour' => '13' ]); 
        Schedule::create([
                'hour_start' => '13:30:00', 'hour_end' => '14:00:00', 'hour' => '13' ]); 
        Schedule::create([
                'hour_start' => '14:00:00', 'hour_end' => '14:30:00', 'hour' => '14' ]); 
        Schedule::create([
                'hour_start' => '14:30:00', 'hour_end' => '15:00:00', 'hour' => '14' ]); 
        Schedule::create([
                'hour_start' => '15:00:00', 'hour_end' => '15:30:00', 'hour' => '15' ]); 
        Schedule::create([
                'hour_start' => '15:30:00', 'hour_end' => '16:00:00', 'hour' => '15' ]); 
        Schedule::create([
                'hour_start' => '16:00:00', 'hour_end' => '16:30:00', 'hour' => '16' ]); 
        Schedule::create([
                'hour_start' => '16:30:00', 'hour_end' => '17:00:00', 'hour' => '16' ]); 
        Schedule::create([
                'hour_start' => '17:00:00', 'hour_end' => '17:30:00', 'hour' => '17' ]); 
        Schedule::create([
                'hour_start' => '17:30:00', 'hour_end' => '18:00:00', 'hour' => '17' ]); 
        Schedule::create([
                'hour_start' => '18:00:00', 'hour_end' => '18:30:00', 'hour' => '18' ]); 
        Schedule::create([
                'hour_start' => '18:30:00', 'hour_end' => '19:00:00', 'hour' => '18' ]); 
        Schedule::create([
                'hour_start' => '19:00:00', 'hour_end' => '19:30:00', 'hour' => '19' ]); 
        Schedule::create([
                'hour_start' => '19:30:00', 'hour_end' => '20:00:00', 'hour' => '19' ]); 
        Schedule::create([
                'hour_start' => '20:00:00', 'hour_end' => '20:30:00', 'hour' => '20' ]); 
        Schedule::create([
                'hour_start' => '20:30:00', 'hour_end' => '21:00:00', 'hour' => '20' ]); 
        Schedule::create([
                'hour_start' => '21:00:00', 'hour_end' => '21:30:00', 'hour' => '21' ]); 
        Schedule::create([
                'hour_start' => '21:30:00', 'hour_end' => '22:00:00', 'hour' => '21' ]); 
        Schedule::create([
                'hour_start' => '22:00:00', 'hour_end' => '22:30:00', 'hour' => '22' ]); 
        Schedule::create([
                'hour_start' => '22:30:00', 'hour_end' => '23:00:00', 'hour' => '22' ]); 
        Schedule::create([
                'hour_start' => '23:00:00', 'hour_end' => '23:30:00', 'hour' => '23' ]); 
        Schedule::create([
                'hour_start' => '23:30:00', 'hour_end' => '00:00:00', 'hour' => '23' ]); 
    }
}
