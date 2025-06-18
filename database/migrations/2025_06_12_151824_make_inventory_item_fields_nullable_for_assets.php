<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->integer('low_stock_threshold')->nullable()->change();
            $table->integer('shareable_quantity')->nullable()->change();
            $table->date('expiry_date')->nullable()->change();
            // price, selling_price already nullable by default in schema
        });
    }

    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->integer('low_stock_threshold')->nullable(false)->default(5)->change();
            $table->integer('shareable_quantity')->nullable()->change(); // likely remains nullable
            $table->date('expiry_date')->nullable()->change(); // likely remains nullable
        });
    }
};
