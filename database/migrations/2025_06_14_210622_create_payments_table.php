<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->decimal('amount', 10, 2);
            $table->string('method'); // 'full' or 'installment'
            $table->integer('installment_no')->nullable(); // null for full, set for installment
            $table->date('due_date')->nullable(); // for installment
            $table->date('paid_at')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'paid', 'overdue'
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
