<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3)->default('AED');
            $table->string('currency', 3);
            $table->decimal('rate', 18, 8);
            $table->timestamp('rate_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'currency']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
};
