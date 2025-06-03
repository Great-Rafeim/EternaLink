<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePlotOccupationsTable extends Migration
{
    public function up()
    {
        Schema::table('plot_occupations', function (Blueprint $table) {
            $table->date('burial_date')->nullable()->after('death_date');
            $table->string('cause_of_death')->nullable()->after('burial_date');
            $table->string('funeral_home')->nullable()->after('cause_of_death');
            $table->string('next_of_kin_name')->nullable()->after('funeral_home');
            $table->string('next_of_kin_contact')->nullable()->after('next_of_kin_name');
            $table->string('interred_by')->nullable()->after('next_of_kin_contact');
        });
    }

    public function down()
    {
        Schema::table('plot_occupations', function (Blueprint $table) {
            $table->dropColumn([
                'burial_date',
                'cause_of_death',
                'funeral_home',
                'next_of_kin_name',
                'next_of_kin_contact',
                'interred_by',
            ]);
        });
    }
}
