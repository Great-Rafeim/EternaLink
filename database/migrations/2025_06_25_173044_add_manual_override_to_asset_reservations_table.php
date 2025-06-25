<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualOverrideToAssetReservationsTable extends Migration
{
    public function up()
    {
        Schema::table('asset_reservations', function (Blueprint $table) {
            $table->boolean('manual_override')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('asset_reservations', function (Blueprint $table) {
            $table->dropColumn('manual_override');
        });
    }
}
