<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('inventory_items', function ($table) {
        $table->integer('low_stock_threshold')->default(5)->after('quantity');
    });
}

public function down()
{
    Schema::table('inventory_items', function ($table) {
        $table->dropColumn('low_stock_threshold');
    });
}

};
