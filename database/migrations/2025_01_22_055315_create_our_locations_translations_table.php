<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('our_locations_translations', function (Blueprint $table) {
            $table->id();
            $table->text('field_values');
            $table->text('language');
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('id')->on('our_locations')->onDelete('cascade');
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
        Schema::dropIfExists('our_locations_translations');
    }
};
