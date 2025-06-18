<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customized_packages', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'approved', 'denied'])
                  ->default('draft')
                  ->after('custom_total_price');
        });
    }

    public function down(): void
    {
        Schema::table('customized_packages', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
