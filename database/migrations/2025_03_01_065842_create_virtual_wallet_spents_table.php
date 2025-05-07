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
        Schema::create('virtual_wallet_spents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_wallet_id')->unsigned();
            $table->string('info')->nullable();
            $table->tinyInteger('is_recharge')->default(0)->nullable();
            $table->double('amount', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('virtual_wallet_id')->references('id')->on('virtual_wallets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_wallet_spents');
    }
};
