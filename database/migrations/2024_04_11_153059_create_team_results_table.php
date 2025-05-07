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
        Schema::create('team_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('journey_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('total_points')->nullable()->default(0);
            $table->tinyInteger('match_won')->nullable()->default(0);
            $table->tinyInteger('match_lost')->nullable()->default(0);
            $table->integer('sets_won')->nullable()->default(0);
            $table->integer('sets_lost')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_results');
    }
};
