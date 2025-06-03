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


    Schema::table('plot_occupations', function (Blueprint $table) {
        $table->timestamp('archived_at')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations_and_occupations', function (Blueprint $table) {
            //
        });
    }
};
