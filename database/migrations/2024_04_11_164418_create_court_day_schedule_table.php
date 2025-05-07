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
        Schema::create('court_day_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_day_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_day_schedules');
    }
};
