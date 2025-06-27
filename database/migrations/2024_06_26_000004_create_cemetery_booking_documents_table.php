<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('cemetery_booking_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cemetery_booking_id');
            $table->enum('type', [
                'death_certificate',
                'burial_permit',
                'construction_permit',
                'proof_of_purchase'
            ]);
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('cemetery_booking_id')->references('id')->on('cemetery_bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cemetery_booking_documents');
    }
};