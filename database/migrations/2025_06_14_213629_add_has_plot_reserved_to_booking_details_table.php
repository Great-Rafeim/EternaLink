<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('booking_details', function (Blueprint $table) {
        $table->boolean('has_plot_reserved')->nullable()->after('cemetery_or_crematory');
    });
}
public function down()
{
    Schema::table('booking_details', function (Blueprint $table) {
        $table->dropColumn('has_plot_reserved');
    });
}

};
