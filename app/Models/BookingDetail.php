<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        // Deceased Personal Details
            'deceased_image',

        'deceased_first_name',
        'deceased_middle_name',
        'deceased_last_name',
        'deceased_nickname',
        'deceased_residence',
        'deceased_sex',
        'deceased_civil_status',
        'deceased_birthday',
        'deceased_age',
        'deceased_date_of_death',
        'deceased_religion',
        'deceased_occupation',
        'deceased_citizenship',
        'deceased_time_of_death',
        'deceased_cause_of_death',
        'deceased_place_of_death',

        // Father's name
        'deceased_father_first_name',
        'deceased_father_middle_name',
        'deceased_father_last_name',

        // Mother's maiden name
        'deceased_mother_first_name',
        'deceased_mother_middle_name',
        'deceased_mother_last_name',

        // Corpse Disposal/Interment
        'corpse_disposal',
        'interment_cremation_date',
        'interment_cremation_time',
        'cemetery_or_crematory',

        // Documents and Release
        'death_cert_registration_no',
        'death_cert_released_to',
        'death_cert_released_date',
        'funeral_contract_no',
        'funeral_contract_released_to',
        'funeral_contract_released_date',
        'official_receipt_no',
        'official_receipt_released_to',
        'official_receipt_released_date',

        // Informant
        'informant_name',
        'informant_age',
        'informant_civil_status',
        'informant_relationship',
        'informant_contact_no',
        'informant_address',

        // Service/Payment/Remarks
        'service',
        'amount',
        'other_fee',
        'deposit',
        'cswd',
        'dswd',
        'remarks',

        // Attestation
        'certifier_name',
        'certifier_relationship',
        'certifier_residence',
        'certifier_amount',
        'certifier_signature',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
