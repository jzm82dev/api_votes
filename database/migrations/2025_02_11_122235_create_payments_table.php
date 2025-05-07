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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 10)->nullable();
            $table->foreignId('club_id')->unsigned();
            $table->string('item', 50)->nullable();
            $table->string('description', 250)->nullable();
            $table->integer('amount')->unsigned(); //Without decimals
            $table->string('currency', 3);
            $table->string('subscription_id')->nullable();
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
        Schema::dropIfExists('payments');
    }
};
