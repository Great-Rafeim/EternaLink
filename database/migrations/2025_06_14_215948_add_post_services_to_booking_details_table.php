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
        $table->string('post_services')->nullable();
    });
}

public function down()
{
    Schema::table('booking_details', function (Blueprint $table) {
        $table->dropColumn('post_services');
    });
}

};
