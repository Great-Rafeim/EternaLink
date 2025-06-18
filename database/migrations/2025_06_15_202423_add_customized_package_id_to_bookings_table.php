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
    Schema::table('bookings', function (Blueprint $table) {
        // DO NOT add the column again
        // $table->unsignedBigInteger('customized_package_id')->nullable()->after('package_id');

        $table->foreign('customized_package_id')
              ->references('id')
              ->on('customized_packages')
              ->onDelete('set null');
    });
}

public function down()
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropForeign(['customized_package_id']);
        // DO NOT drop the column unless you're cleaning it up fully
        // $table->dropColumn('customized_package_id');
    });
}


};
