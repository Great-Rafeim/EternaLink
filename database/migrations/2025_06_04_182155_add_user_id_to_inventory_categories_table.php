<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            // Only add if not exists to prevent errors
            if (!Schema::hasColumn('inventory_categories', 'funeral_home_id')) {
                $table->unsignedBigInteger('funeral_home_id')->nullable()->after('id');
                $table->foreign('funeral_home_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_categories', 'funeral_home_id')) {
                $table->dropForeign(['funeral_home_id']);
                $table->dropColumn('funeral_home_id');
            }
        });
    }
};
