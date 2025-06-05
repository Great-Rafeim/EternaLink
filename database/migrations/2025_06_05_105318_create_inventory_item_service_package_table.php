<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryItemServicePackageTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_item_service_package', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_package_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('quantity')->default(1); // if you want to specify how many of each item in the package
            $table->timestamps();

            $table->foreign('service_package_id')->references('id')->on('service_packages')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_item_service_package');
    }
}

