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
    Schema::create('agent_client', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('agent_id');
        $table->unsignedBigInteger('client_id');
        $table->string('case')->nullable(); // (Optional) description or tag for assignment
        $table->timestamps();

        // If you want, add constraints:
        $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
        $table->unique(['agent_id', 'client_id', 'case']); // Prevent duplicates per case
    });
}

public function down()
{
    Schema::dropIfExists('agent_client');
}

};
