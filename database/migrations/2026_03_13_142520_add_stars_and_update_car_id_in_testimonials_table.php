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
        Schema::table('testimonials', function (Blueprint $table) {
            $table->tinyInteger('stars')->nullable()->after('car_id');
        });

        // Make car_id nullable
        DB::statement("ALTER TABLE testimonials MODIFY car_id INT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('stars');
        });

        // Revert car_id back to NOT NULL
        DB::statement("ALTER TABLE testimonials MODIFY car_id INT NOT NULL");
    }
};
