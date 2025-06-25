<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterStatusEnumAddBorrowedFromPartnerToInventoryItemsTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `inventory_items`
            MODIFY `status` ENUM('available','in_use','maintenance','reserved','shared_to_partner','borrowed_from_partner')
            NOT NULL DEFAULT 'available'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `inventory_items`
            MODIFY `status` ENUM('available','in_use','maintenance','reserved','shared_to_partner')
            NOT NULL DEFAULT 'available'");
    }
}
