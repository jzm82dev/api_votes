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
        Schema::create('virtual_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->unsigned();
            $table->string('name');
            $table->string('surname');
            $table->string('mobile', 50);
            $table->double('amount', 8, 2)->nullable();
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
        Schema::dropIfExists('virtual_wallets');
    }
};
