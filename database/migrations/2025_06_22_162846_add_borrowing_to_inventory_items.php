<?php

// database/migrations/2024_06_22_000001_add_borrowing_to_inventory_items.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBorrowingToInventoryItems extends Migration
{
    public function up()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('is_temporary')->default(0)->after('shareable_quantity');
            $table->unsignedBigInteger('original_asset_id')->nullable()->after('is_temporary');
            $table->dateTime('borrowed_until')->nullable()->after('original_asset_id');
        });
    }

    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['is_temporary', 'original_asset_id', 'borrowed_until']);
        });
    }
}

