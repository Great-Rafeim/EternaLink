<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignatureImagesToBookingDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('booking_details', function (Blueprint $table) {
            // Signature image fields (Base64 or path)
            $table->longText('certifier_signature_image')->nullable()->after('certifier_signature');
            $table->longText('death_cert_released_signature')->nullable()->after('death_cert_released_to');
            $table->longText('funeral_contract_released_signature')->nullable()->after('funeral_contract_released_to');
            $table->longText('official_receipt_released_signature')->nullable()->after('official_receipt_released_to');
        });
    }

    public function down()
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->dropColumn([
                'certifier_signature_image',
                'death_cert_released_signature',
                'funeral_contract_released_signature',
                'official_receipt_released_signature',
            ]);
        });
    }
}
