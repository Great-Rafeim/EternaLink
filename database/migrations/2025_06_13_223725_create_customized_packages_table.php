<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_customized_packages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customized_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->unsignedBigInteger('original_package_id');
            $table->decimal('custom_total_price', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('original_package_id')->references('id')->on('service_packages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customized_packages');
    }
};
