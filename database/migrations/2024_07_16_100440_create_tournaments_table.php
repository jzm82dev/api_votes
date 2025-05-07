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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->tinyInteger('is_draft')->default(1)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('date_starts_registration')->nullable();
            $table->date('date_ends_registration')->nullable();
            $table->string('hour_starts_registration')->nullable();
            $table->string('hour_ends_registration')->nullable();
            $table->double('price', 8, 2)->nullable();
            $table->double('price_member', 8, 2)->nullable();
            $table->integer('time_per_match');
            $table->tinyInteger('draw_generated')->default(0)->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
