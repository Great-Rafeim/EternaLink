<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agent_client_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->after('client_id');
            // If you want a foreign key (optional):
             $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('agent_client_requests', function (Blueprint $table) {
            $table->dropColumn('booking_id');
        });
    }
};

