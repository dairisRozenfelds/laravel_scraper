<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScraperPigiameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scraper_pigiame', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ad_id')->unsigned();
            $table->string('location')->nullable();
            $table->string('region')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('price', 24, 4)->nullable();
            $table->string('condition')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('transmission')->nullable();
            $table->string('drive_type')->nullable();
            $table->bigInteger('mileage')->nullable();
            $table->string('mileage_unit')->nullable();
            $table->smallInteger('build_year')->unsigned()->nullable();
            $table->json('car_features')->nullable();
            $table->dateTime('ad_date_inserted')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scraper_pigiame');
    }
}
