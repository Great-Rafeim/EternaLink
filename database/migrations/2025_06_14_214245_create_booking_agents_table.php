<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingAgentsTable extends Migration
{
    public function up()
    {
        Schema::create('booking_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('need_agent')->nullable(); // 'yes', 'no'
            $table->string('agent_type')->nullable(); // 'client', 'parlor'
            $table->string('client_agent_email')->nullable();
            // Optionally, reference a user_id if assigned
            $table->unsignedBigInteger('agent_user_id')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_agents');
    }
}
