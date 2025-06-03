<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('category_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('package_category_id');
        $table->string('name');         // e.g. "Urn", "Hearse Van"
        $table->integer('quantity')->default(1);
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2)->default(0);

        $table->timestamps();

        $table->foreign('package_category_id')->references('id')->on('package_categories')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_items');
    }
};
