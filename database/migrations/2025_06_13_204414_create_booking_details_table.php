<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');

            // Deceased Personal Details
            $table->string('deceased_first_name');
            $table->string('deceased_middle_name')->nullable();
            $table->string('deceased_last_name');
            $table->string('deceased_nickname')->nullable();
            $table->string('deceased_residence')->nullable();
            $table->string('deceased_sex', 10)->nullable();
            $table->string('deceased_civil_status', 30)->nullable();
            $table->date('deceased_birthday')->nullable();
            $table->integer('deceased_age')->nullable();
            $table->date('deceased_date_of_death')->nullable();
            $table->string('deceased_religion')->nullable();
            $table->string('deceased_occupation')->nullable();
            $table->string('deceased_citizenship')->nullable();
            $table->string('deceased_time_of_death')->nullable();
            $table->string('deceased_cause_of_death')->nullable();
            $table->string('deceased_place_of_death')->nullable();

            // Father's name (First, Middle, Last)
            $table->string('deceased_father_first_name')->nullable();
            $table->string('deceased_father_middle_name')->nullable();
            $table->string('deceased_father_last_name')->nullable();

            // Mother's maiden name (First, Middle, Last)
            $table->string('deceased_mother_first_name')->nullable();
            $table->string('deceased_mother_middle_name')->nullable();
            $table->string('deceased_mother_last_name')->nullable();

            // Corpse Disposal/Interment
            $table->string('corpse_disposal')->nullable();
            $table->date('interment_cremation_date')->nullable();
            $table->string('interment_cremation_time')->nullable();
            $table->string('cemetery_or_crematory')->nullable();

            // Documents and Release
            $table->string('death_cert_registration_no')->nullable();
            $table->string('death_cert_released_to')->nullable();
            $table->date('death_cert_released_date')->nullable();

            $table->string('funeral_contract_no')->nullable();
            $table->string('funeral_contract_released_to')->nullable();
            $table->date('funeral_contract_released_date')->nullable();

            $table->string('official_receipt_no')->nullable();
            $table->string('official_receipt_released_to')->nullable();
            $table->date('official_receipt_released_date')->nullable();

            // Informant
            $table->string('informant_name')->nullable();
            $table->integer('informant_age')->nullable();
            $table->string('informant_civil_status')->nullable();
            $table->string('informant_relationship')->nullable();
            $table->string('informant_contact_no')->nullable();
            $table->string('informant_address')->nullable();

            // Service/Payment/Remarks
            $table->string('service')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('other_fee')->nullable();
            $table->string('deposit')->nullable();
            $table->string('cswd')->nullable();
            $table->string('dswd')->nullable();
            $table->text('remarks')->nullable();

            // Attestation
            $table->string('certifier_name')->nullable();
            $table->string('certifier_relationship')->nullable();
            $table->string('certifier_residence')->nullable();
            $table->decimal('certifier_amount', 10, 2)->nullable();
            $table->string('certifier_signature')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_details');
    }
}
