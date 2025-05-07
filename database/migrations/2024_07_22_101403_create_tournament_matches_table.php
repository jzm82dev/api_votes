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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('local_couple_id')->nullable()->constrained('couples')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('visiting_couple_id')->nullable()->constrained('couples')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('round');
            $table->integer('order');
            $table->integer('main_draw')->nullable();
            $table->integer('back_draw')->nullable();
            $table->string('result_set_1', 5)->nullable();
            $table->string('result_set_2', 5)->nullable();
            $table->string('result_set_3', 5)->nullable();
            $table->dateTime('match_date')->nullable();
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
        Schema::dropIfExists('tournament_matches');
    }
};
