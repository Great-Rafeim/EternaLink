<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterInventoryItemsStatusEnum extends Migration
{
    public function up()
    {
        // For MySQL you have to run a raw statement
        DB::statement("ALTER TABLE inventory_items MODIFY COLUMN status ENUM('available','in_use','maintenance','reserved') NOT NULL DEFAULT 'available'");
    }

    public function down()
    {
        // Revert to previous enum (remove 'reserved')
        DB::statement("ALTER TABLE inventory_items MODIFY COLUMN status ENUM('available','in_use','maintenance') NOT NULL DEFAULT 'available'");
    }
}
