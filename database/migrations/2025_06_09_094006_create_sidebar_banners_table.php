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
        Schema::create('sidebar_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('image');
            $table->string('redirect_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('language',100);
            $table->integer('status')->default(1);
            $table->softDeletes();
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
        Schema::dropIfExists('sidebar_banners');
    }
};
