<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.   // np va bien por el tema de la foranea... mejor hacerla manual
     * ALTER TABLE `reservations` ADD CONSTRAINT `reservations_user_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->integer('member_id')->nullable()->after('monitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            //
        });
    }
};
