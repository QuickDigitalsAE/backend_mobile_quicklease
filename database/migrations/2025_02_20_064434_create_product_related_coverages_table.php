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
        Schema::create('product_related_coverages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coverage_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('less_30_days_price', 10, 2)->nullable();
            $table->decimal('more_30_days_price', 10, 2)->nullable();
            $table->foreign('coverage_id')->references('id')->on('product_coverages')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
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
        Schema::dropIfExists('product_related_coverages');
    }
};
