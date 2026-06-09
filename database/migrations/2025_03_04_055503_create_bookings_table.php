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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->text('order_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('email');
            $table->string('pickup_city');
            $table->string('pickup_address')->nullable();
            $table->string('pickup_date_time');
            $table->string('return_city');
            $table->string('return_address')->nullable();
            $table->string('return_date_time');
            $table->string('car_month')->nullable();
            $table->string('car_monthly_price')->nullable();
            $table->string('deposit_type')->nullable();
            $table->string('deposit_selected_tab')->nullable();
            $table->string('deposit_price')->nullable();
            $table->string('total_days');
            $table->string('promo_code')->nullable();
            $table->string('promo_discount')->nullable();
            $table->string('pay_now_discount')->nullable();
            $table->string('summary_total_amount')->nullable();
            $table->string('summary_total_vat')->nullable();
            $table->string('total_discount_incl_vat')->nullable();
            $table->string('total_price');
            $table->json('extras')->nullable();
            $table->text('booking_page_slug');
            $table->string('payment_type');
            $table->string('payment_status');
            $table->string('transaction_id');
            $table->string('booking_status');
            $table->tinyInteger('accept_terms')->default(0)->comment('1 for yes, 0 for no');
            $table->tinyInteger('valid_driving_license')->default(0)->comment('1 for yes, 0 for no');
            $table->tinyInteger('valid_passport')->default(0)->comment('1 for yes, 0 for no');
            $table->tinyInteger('driver_age_above')->default(0)->comment('1 for yes, 0 for no');
            $table->string('card_payment')->nullable();
            $table->integer('partial_percentage')->nullable();
            $table->string('partial_amount')->nullable();
            $table->unsignedBigInteger('updated_by');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
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
        Schema::dropIfExists('bookings');
    }
};
