<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBorrowingColumnsToInventoryItemsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('is_borrowed')->default(0)->after('is_temporary');
            $table->unsignedBigInteger('borrowed_from_id')->nullable()->after('is_borrowed');
            $table->unsignedBigInteger('borrowed_reservation_id')->nullable()->after('borrowed_from_id');
            $table->datetime('borrowed_start')->nullable()->after('borrowed_reservation_id');
            $table->datetime('borrowed_end')->nullable()->after('borrowed_start');
        });
    }

    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_borrowed',
                'borrowed_from_id',
                'borrowed_reservation_id',
                'borrowed_start',
                'borrowed_end'
            ]);
        });
    }
}
