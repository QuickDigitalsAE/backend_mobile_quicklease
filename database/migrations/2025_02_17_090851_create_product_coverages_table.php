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
        Schema::create('product_coverages', function (Blueprint $table) {
            $table->id();
            $table->decimal('less_30_days_price', 10, 2)->nullable();
            $table->decimal('more_30_days_price', 10, 2)->nullable();
            $table->text('prices_by_locations')->nullable();
            $table->tinyInteger('field_required')->default(0)->comment('1 for in_stock, 0 for out_stock');
            $table->tinyInteger('checked_by_default')->default(0)->comment('1 for show, 0 for hide');
            $table->tinyInteger('coverage_status')->default(1)->comment('1 for enable, 0 for disable');
            $table->tinyInteger('countable_value')->default(0)->comment('1 for show, 0 for hide');
            $table->tinyInteger('per_day_price')->default(0)->comment('1 for show, 0 for hide');
            $table->tinyInteger('address_is_required')->default(0)->comment('1 for show, 0 for hide');
            $table->tinyInteger('vat_is_applicable')->default(0)->comment('1 for show, 0 for hide');
            $table->tinyInteger('recommended')->default(0)->comment('1 = yes, 0 = no');
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
        Schema::dropIfExists('product_coverages');
    }
};
