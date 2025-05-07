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
        Schema::create('couple_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('total_points')->nullable()->default(0);
            $table->unsignedInteger('matches_played')->nullable()->default(0);
            $table->unsignedInteger('matchs_won')->nullable()->default(0);
            $table->unsignedInteger('matchs_lost')->nullable()->default(0);
            $table->unsignedInteger('games_won')->nullable()->default(0);
            $table->unsignedInteger('games_lost')->nullable()->default(0);
            $table->unsignedInteger('sets_won')->nullable()->default(0);
            $table->unsignedInteger('sets_lost')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couple_results');
    }
};
