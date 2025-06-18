<?php

// In the generated migration:
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWakeDatesToBookingDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->date('wake_start_date')->nullable()->after('remarks');
            $table->date('wake_end_date')->nullable()->after('wake_start_date');
        });
    }

    public function down()
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->dropColumn(['wake_start_date', 'wake_end_date']);
        });
    }
}

