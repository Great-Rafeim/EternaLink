<?php

// database/migrations/2024_06_22_000002_add_borrowed_item_id_to_asset_reservations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBorrowedItemIdToAssetReservations extends Migration
{
    public function up()
    {
        Schema::table('asset_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('borrowed_item_id')->nullable()->after('shared_with_partner_id');
        });
    }

    public function down()
    {
        Schema::table('asset_reservations', function (Blueprint $table) {
            $table->dropColumn('borrowed_item_id');
        });
    }
}
