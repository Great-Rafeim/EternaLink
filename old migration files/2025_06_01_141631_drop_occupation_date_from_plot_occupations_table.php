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
    Schema::table('plot_occupations', function (Blueprint $table) {
        $table->dropColumn('occupation_date');
    });
}

public function down()
{
    Schema::table('plot_occupations', function (Blueprint $table) {
        $table->date('occupation_date')->nullable();
    });
}

};
