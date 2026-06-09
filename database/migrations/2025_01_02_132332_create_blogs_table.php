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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->text('slug');
            $table->text('image')->nullable();
            $table->tinyInteger('blog_status')->default(1)->comment('1 for active, 0 for inactive');
            $table->text('blog_schedule')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->tinyInteger('table_of_content')->default(0)->comment('1 for yes, 0 for no');
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
        Schema::dropIfExists('blogs');
        Schema::table('blogs', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
