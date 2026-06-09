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
        Schema::create('our_locations', function (Blueprint $table) {
            $table->id();
            $table->text('slug');
            $table->text('banner_image')->nullable();
            $table->text('car_ids')->nullable();
            $table->tinyInteger('location_status')->default(1)->comment('1 for active, 0 for inactive');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('our_locations');
    }
};
