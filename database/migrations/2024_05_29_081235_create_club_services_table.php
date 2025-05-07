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
        Schema::create('club_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->tinyInteger('pool')->default(0)->nullable();
            $table->tinyInteger('gym')->default(0)->nullable();
            $table->tinyInteger('playroom')->default(0)->nullable();
            $table->tinyInteger('cafe')->default(0)->nullable();
            $table->tinyInteger('restaurant')->default(0)->nullable();
            $table->tinyInteger('shop')->default(0)->nullable();
            $table->json('more_services')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_services');
    }
};
