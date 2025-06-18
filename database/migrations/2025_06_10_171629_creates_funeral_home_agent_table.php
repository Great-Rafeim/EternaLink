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
    Schema::create('funeral_home_agent', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('funeral_user_id'); // Funeral parlor/staff user id
        $table->unsignedBigInteger('agent_user_id');   // Agent user id
        $table->timestamps();

        $table->foreign('funeral_user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('agent_user_id')->references('id')->on('users')->onDelete('cascade');
        $table->unique(['funeral_user_id', 'agent_user_id']); // Optional: prevent duplicates
    });
}

public function down()
{
    Schema::dropIfExists('funeral_home_agent');
}

};
