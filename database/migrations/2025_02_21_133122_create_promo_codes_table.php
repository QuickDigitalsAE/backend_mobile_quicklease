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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->integer('page_id')->nullable();
            $table->string('target_type');
            $table->bigInteger('promotion_id')->nullable();
            $table->string('code_title');
            $table->string('code_type');
            $table->string('code_value');
            $table->string('code');
            $table->tinyInteger('code_status')->default(1)->comment('1 for enable, 0 for disable');
            $table->string('expires_at')->nullable();
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
        Schema::dropIfExists('promo_codes');
    }
};
