<?php

// database/migrations/2024_06_05_000001_create_resource_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourceTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('resource_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_request_id');
            $table->unsignedBigInteger('inventory_item_id'); // The item being moved (provider's stock)
            $table->date('fulfilled_at')->nullable(); // Date of actual transfer
            $table->string('status')->default('in_transit'); // in_transit, delivered, cancelled, returned, etc.
            $table->timestamps();

            // Foreign keys
            $table->foreign('resource_request_id')->references('id')->on('resource_requests');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resource_transactions');
    }
}
