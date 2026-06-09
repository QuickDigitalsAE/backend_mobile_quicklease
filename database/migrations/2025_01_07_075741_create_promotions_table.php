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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->text('slug');
            $table->text('image')->nullable();
            $table->text('banner_image')->nullable();
            $table->tinyInteger('promotion_status')->default(1)->comment('1 for active, 0 for inactive');
            $table->text('brand_logo')->nullable();
            $table->string('car_ids');
            $table->string('schedule_date')->nullable();
            $table->string('page_type')->default('promotion');
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
        Schema::dropIfExists('promotions');
    }
};
