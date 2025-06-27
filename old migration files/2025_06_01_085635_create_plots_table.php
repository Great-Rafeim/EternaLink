<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlotsTable extends Migration
{
    public function up()
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('plot_number')->unique();
            $table->string('section')->nullable();
            $table->string('block')->nullable();
            $table->enum('type', ['single', 'double', 'family']);
            $table->enum('status', ['available', 'reserved', 'occupied'])->default('available');
            $table->string('deceased_name')->nullable();       // For reserved plots
            $table->string('deceased_name')->nullable();    // For occupied plots
            $table->date('birth_date')->nullable();         // For occupied plots
            $table->date('death_date')->nullable();         // For occupied plots
            $table->timestamp('purchased_at')->nullable();  // For reserved or occupied
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plots');
    }
}
