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
        Schema::create('game_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->integer('game_number');
            $table->foreignId('local_player_1')->constrained('players')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('local_player_2')->constrained('players')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('visiting_player_1')->constrained('players')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('visiting_player_2')->constrained('players')->onDelete('cascade')->onUpdate('cascade');
            $table->string('result_set_1', 5)->nullable();
            $table->string('result_set_2', 5)->nullable();
            $table->string('result_set_3', 5)->nullable();
            $table->timestamp('cron_executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_items');
    }
};
