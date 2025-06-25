<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReservationModeToInventoryCategories extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->enum('reservation_mode', ['continuous', 'single_event'])
                  ->default('continuous')
                  ->after('is_asset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropColumn('reservation_mode');
        });
    }
}
