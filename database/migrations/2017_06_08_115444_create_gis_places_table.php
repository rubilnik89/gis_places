<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGisPlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gis_places', function (Blueprint $table) {
            $table->decimal('lat', 10, 8);
            $table->decimal('lon', 11, 8);
            $table->string('name');
            $table->string('id')->unique();
            $table->string('type');
            $table->json('options');
            $table->json('photos');
            $table->string('city');
            $table->boolean('photo_uploaded')->default(false);
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
        Schema::dropIfExists('gis_places');
    }
}
