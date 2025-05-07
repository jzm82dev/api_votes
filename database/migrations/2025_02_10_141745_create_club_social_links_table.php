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
        Schema::create('club_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->unsigned();
            $table->string('instagram_link', 150)->nullable();
            $table->string('twiter_link', 150)->nullable();
            $table->string('facebook_link', 150)->nullable();
            $table->string('youtube_link', 150)->nullable();
            $table->string('linkedin_link', 150)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('club_id')->references('id')->on('clubs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_social_links');
    }
};
