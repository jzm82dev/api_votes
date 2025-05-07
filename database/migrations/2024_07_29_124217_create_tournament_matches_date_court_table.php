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
        Schema::create('tournament_matches_date_court', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('tournament_match_id')->nullable()->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('court_id')->nullable()->constrained('couples')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('date');
            $table->tinyInteger('match_finished')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches_date_court');
    }
};
