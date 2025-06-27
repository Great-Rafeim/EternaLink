<?php
// database/migrations/2024_06_26_000002_add_cemetery_id_to_plots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->unsignedBigInteger('cemetery_id')->nullable()->after('id');
            // Do NOT add FK constraint yet!
        });
    }

    public function down()
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->dropColumn('cemetery_id');
        });
    }
};


