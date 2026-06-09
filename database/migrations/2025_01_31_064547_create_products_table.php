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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalog_id');
            $table->text('additional_catalog_ids')->nullable();
            $table->text('car_locations')->nullable();
            $table->text('slug');
            $table->string('daily_price')->nullable();
            $table->string('old_daily_price')->nullable();
            $table->string('weekly_price')->nullable();
            $table->string('old_weekly_price')->nullable();
            $table->string('monthly_price')->nullable();
            $table->string('old_monthly_price')->nullable();
            $table->text('flexible_cars_monthly_prices')->nullable();
            $table->text('personal_cars_monthly_prices')->nullable();
            $table->string('monthly_installment_24_months')->nullable();
            $table->string('monthly_installment_36_months')->nullable();
            $table->string('installment_per_month')->nullable();
            $table->string('installment_per_month_with_down')->nullable();
            $table->string('installment_per_month_final_term')->nullable();
            $table->string('down_payment')->nullable();
            $table->string('security_deposit')->nullable();
            $table->string('security_deposit_waiver_daily')->nullable();
            $table->string('security_deposit_waiver_monthly')->nullable();
            $table->string('vehicle_type');
            $table->string('main_image');
            $table->text('car_images')->nullable();
            $table->string('specification_auto')->nullable();
            $table->string('year')->nullable();
            $table->string('model')->nullable();
            $table->string('pay_now_discount')->nullable();
            $table->tinyInteger('featured')->default(0)->comment('1 for yes, 0 for no');
            $table->tinyInteger('promo_status')->default(0)->comment('1 for yes, 0 for no');
            $table->tinyInteger('stock_status')->default(1)->comment('1 for in_stock, 0 for out_stock');
            $table->tinyInteger('product_status')->default(1)->comment('1 for enable, 0 for disable');
            $table->tinyInteger('book_now_button')->default(1)->comment('1 for show, 0 for hide');
            $table->tinyInteger('show_on_home')->default(1)->comment('1 for show, 0 for hide');
            $table->tinyInteger('hidden_from_list')->default(1)->comment('1 for show, 0 for hide');
            $table->tinyInteger('show_documents')->default(1)->comment('1 for show, 0 for hide');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('catalog_id')->references('id')->on('catalogs')->onDelete('cascade');
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
        Schema::dropIfExists('products');
    }
};
