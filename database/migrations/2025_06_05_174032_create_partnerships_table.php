<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnershipsTable extends Migration
{
    public function up()
    {
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id'); // ID of parlor sending the request
            $table->unsignedBigInteger('partner_id');   // ID of parlor being requested
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('partner_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['requester_id', 'partner_id']); // prevent duplicates
        });
    }

    public function down()
    {
        Schema::dropIfExists('partnerships');
    }
}
