<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInventoryItemServicePackageForAssets extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('service_package_components', function (Blueprint $table) {
            // Add the category column for bookable asset linkage
            $table->unsignedBigInteger('inventory_category_id')->nullable()->after('inventory_item_id');

            // Make inventory_item_id nullable for category-only linkage
            $table->unsignedBigInteger('inventory_item_id')->nullable()->change();

            // Add index for the new column
            $table->index('inventory_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_package_components', function (Blueprint $table) {
            $table->dropIndex(['inventory_category_id']);
            $table->dropColumn('inventory_category_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable(false)->change();
        });
    }
}
