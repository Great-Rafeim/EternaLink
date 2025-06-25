<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackageAssetCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('package_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_package_id');
            $table->unsignedBigInteger('inventory_category_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('service_package_id')->references('id')->on('service_packages')->onDelete('cascade');
            $table->foreign('inventory_category_id')->references('id')->on('inventory_categories')->onDelete('cascade');
            $table->unique(['service_package_id', 'inventory_category_id'], 'pkg_asset_cat_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('package_asset_categories');
    }
}
