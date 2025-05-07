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
        Schema::create('club_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('day_id');
            $table->string('day_name');
            //$table->string('opening_time')->nullable();
            //$table->string('closing_time')->nullable();
            $table->tinyInteger('closed')->default(0)->nullable();
            //$table->foreignId('opening_time_id')->constrained('schedules')->nullable();
            //$table->foreignId('closing_time_id')->constrained('schedules')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_schedule_days');
    }
};
