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
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parent_id')->nullable();
            $table->text('slug');
            $table->text('banner_image')->nullable();
            $table->text('brand_logo')->nullable();
            $table->text('car_ids')->nullable();
            $table->tinyInteger('sec_one_slider_status')->default(1);
            $table->tinyInteger('sec_two_slider_status')->default(1);
            $table->tinyInteger('sec_three_slider_status')->default(1);
            $table->tinyInteger('catalog_status')->default(1)->comment('1 for enable, 0 for disable');
            $table->string('type');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('catalogs');
    }
};
