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
        Schema::table('enquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('enquiries', 'show_lease')) {
                $table->string('show_lease')->nullable()->after('lease_to_own');
            }

            if (!Schema::hasColumn('enquiries', 'km')) {
                $table->string('km')->nullable()->after('show_lease');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('enquiries', 'km')) {
                $table->dropColumn('km');
            }

            if (Schema::hasColumn('enquiries', 'show_lease')) {
                $table->dropColumn('show_lease');
            }
        });
    }
};
