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
        $table->unsignedBigInteger('plot_id')->nullable()->after('booking_id');
        $table->foreign('plot_id')->references('id')->on('plots')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('booking_details', function (Blueprint $table) {
        $table->dropForeign(['plot_id']);
        $table->dropColumn('plot_id');
    });
}

};
