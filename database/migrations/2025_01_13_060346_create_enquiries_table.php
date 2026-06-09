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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('car_name')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_last_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->string('from_datetime')->nullable();
            $table->string('to_datetime')->nullable();
            $table->string('form_type')->nullable();
            $table->string('referer_page_slug')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('form_status')->nullable();
            $table->string('period')->nullable();
            $table->string('lease_to_own')->nullable();
            $table->text('client_comments')->nullable();
            $table->string('language', 100)->nullable();
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
        Schema::dropIfExists('enquiries');
    }
};
