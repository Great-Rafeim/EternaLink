<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_customized_package_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customized_package_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customized_package_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('substitute_for')->nullable(); // If this item substitutes an original item
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('customized_package_id')->references('id')->on('customized_packages')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('substitute_for')->references('id')->on('inventory_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customized_package_items');
    }
};

