<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('popup_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('status')->default(1);
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('attachment')->nullable();
            $table->string('redirect_link')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('popup_banners');
    }
};
