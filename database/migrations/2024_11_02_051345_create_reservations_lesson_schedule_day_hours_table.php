<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_lesson_day_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_lesson_day_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            //$table->integer('reservation_lesson_days_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('opening_time')->nullable();
            $table->string('closing_time')->nullable();
            $table->foreignId('opening_time_id')->constrained('schedules')->nullable();
            $table->foreignId('closing_time_id')->constrained('schedules')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations_lesson_chedule_day_hours');
    }
};
