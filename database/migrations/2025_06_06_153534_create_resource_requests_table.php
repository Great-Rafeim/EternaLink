<?php

// database/migrations/2024_06_05_000000_create_resource_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourceRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('resource_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id'); // The user making the request
            $table->unsignedBigInteger('provider_id');  // The user providing the item
            $table->unsignedBigInteger('requested_item_id'); // The item requested
            $table->unsignedBigInteger('provider_item_id');  // The partner's shareable item being requested

            $table->integer('quantity');
            $table->text('purpose');
            $table->date('preferred_date')->nullable();
            $table->string('delivery_method');
            $table->text('notes')->nullable();
            $table->string('contact_name');
            $table->string('contact_mobile')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('location')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled', 'cancelled'])->default('pending');

            $table->timestamps();

            // Foreign keys
            $table->foreign('requester_id')->references('id')->on('users');
            $table->foreign('provider_id')->references('id')->on('users');
            $table->foreign('requested_item_id')->references('id')->on('inventory_items');
            $table->foreign('provider_item_id')->references('id')->on('inventory_items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resource_requests');
    }
}
