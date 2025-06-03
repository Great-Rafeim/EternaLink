<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlotOccupationsTable extends Migration
{
    public function up()
    {
        Schema::create('plot_occupations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plot_id')->constrained('plots')->onDelete('cascade');

            $table->string('deceased_name')->nullable(false);
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();

            $table->date('occupation_date')->nullable(); // date plot was occupied
            $table->text('notes')->nullable(); // optional extra info

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plot_occupations');
    }
}
