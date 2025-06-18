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
    Schema::table('funeral_home_agent', function (Blueprint $table) {
        $table->string('status')->default('active')->after('agent_user_id');
    });
}

public function down()
{
    Schema::table('funeral_home_agent', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}

};
