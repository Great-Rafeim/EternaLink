<?php

// database/migrations/2024_06_17_000002_create_asset_reservations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('asset_reservations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_item_id');  // Bookable asset
            $table->unsignedBigInteger('booking_id');         // Related booking
            $table->dateTime('reserved_start');               // When reservation starts
            $table->dateTime('reserved_end');                 // When it ends
            $table->enum('status', ['reserved', 'in_use', 'completed', 'cancelled'])->default('reserved');
            $table->unsignedBigInteger('created_by');         // Who created the reservation (user_id)
            $table->timestamps();

            // Foreign keys
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Composite index for quick conflict checks
            $table->index(['inventory_item_id', 'reserved_start', 'reserved_end'], 'asset_item_reserve_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_reservations');
    }
}
