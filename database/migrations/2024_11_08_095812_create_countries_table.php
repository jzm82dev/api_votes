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
        Schema::create('countries', function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->string('name',100);
            $table->char('iso3',3)->nullable();
            $table->char('numeric_code',3)->nullable();
            $table->char('iso2',2)->nullable();
            $table->string('phonecode')->nullable();
            $table->string('capital')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('tld')->nullable();
            $table->string('native')->nullable();
            $table->string('region')->nullable();
            $table->string('subregion')->nullable();
            $table->text('timezones')->nullable();
            $table->text('translations')->nullable();
            $table->decimal('latitude',10,8)->nullable();
            $table->decimal('longitude',11,8)->nullable();
            $table->string('emoji',191)->nullable();
            $table->string('emojiU',191)->nullable();
            //$table->timestamp('created_at')->nullable();
            //$table->timestamp('updated_at');
            $table->integer('flag')->default('1');
            $table->string('wikiDataId')->nullable();
            $table->string('is_default', 6)->default('no');
            $table->string('created_at')->nullable();
            $table->string('updated_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
