<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('client_user_id')->constrained('users');
            $table->foreignId('funeral_home_id')->constrained('users');
            $table->foreignId('package_id')->constrained('service_packages');
            $table->foreignId('agent_user_id')->nullable()->constrained('users');

            // Status and extra info
            $table->string('status')->default('pending'); // e.g., pending, assigned, confirmed, declined
            $table->text('details')->nullable(); // For form data or notes

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}

