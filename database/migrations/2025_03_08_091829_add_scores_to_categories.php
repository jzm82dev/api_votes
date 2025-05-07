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
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('points_per_win_2_0')->nullable()->after('type');
            $table->integer('points_per_win_2_1')->nullable()->after('points_per_win_2_0');
            $table->integer('points_per_lost_0_2')->nullable()->after('points_per_win_2_1');
            $table->integer('points_per_lost_1_2')->nullable()->after('points_per_lost_0_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
};
