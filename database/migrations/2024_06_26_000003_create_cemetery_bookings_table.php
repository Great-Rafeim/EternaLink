<?php
// database/migrations/2024_06_26_000003_create_cemetery_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cemetery_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cemetery_id');
            $table->unsignedBigInteger('plot_id')->nullable();
            $table->string('casket_size');
            $table->date('interment_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cemetery_id')->references('id')->on('cemeteries');
            $table->foreign('plot_id')->references('id')->on('plots');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cemetery_bookings');
    }
};


