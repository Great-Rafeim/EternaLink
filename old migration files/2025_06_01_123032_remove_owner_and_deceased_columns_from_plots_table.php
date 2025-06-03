<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveOwnerAndDeceasedColumnsFromPlotsTable extends Migration
{
    public function up()
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'deceased_name', 'birth_date', 'death_date']);
        });
    }

    public function down()
    {
        Schema::table('plots', function (Blueprint $table) {
            $table->string('owner_name')->nullable();
            $table->string('deceased_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
        });
    }
}
