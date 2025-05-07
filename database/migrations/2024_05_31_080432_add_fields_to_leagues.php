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
        Schema::table('leagues', function (Blueprint $table) {
            $table->integer('points_per_win')->after('name')->default(0)->nullable();
            $table->integer('points_per_set_losser')->after('points_per_win')->default(0)->nullable();
            $table->date('start_date')->after('points_per_set_losser')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            //
        });
    }
};
