<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_image_to_inventory_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToInventoryItemsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('image')->nullable()->after('brand');
        });
    }

    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
