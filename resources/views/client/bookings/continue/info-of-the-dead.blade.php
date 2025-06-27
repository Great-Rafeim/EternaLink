{{-- resources/views/client/bookings/continue/info-of-the-dead.blade.php --}}

<x-client-layout>





    <div class="container py-5">
        <h2 class="fw-bold mb-4" style="color: #1565c0;">
            <i class="bi bi-person-vcard me-2"></i> Personal & Service Details
        </h2>
        {{-- Show alert or disable form fields if not editable --}}


        <form action="{{ route('client.bookings.details.update', $booking->id) }}" method="POST" class="bg-white rounded shadow-sm p-4">
            @csrf

            {{-- 1. PERSONAL DETAILS (DECEASED) --}}
            <div class="card mb-4 border-0">
                <div class="card-header fw-semibold bg-light">A. Deceased Personal Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input name="deceased_first_name" type="text" class="form-control" required
                            value="{{ old('deceased_first_name', $detail->deceased_first_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input name="deceased_middle_name" type="text" class="form-control"
                            value="{{ old('deceased_middle_name', $detail->deceased_middle_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input name="deceased_last_name" type="text" class="form-control" required
                            value="{{ old('deceased_last_name', $detail->deceased_last_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nickname</label>
                        <input name="deceased_nickname" type="text" class="form-control"
                            value="{{ old('deceased_nickname', $detail->deceased_nickname ?? '') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Residence (House No., St., Barangay, City/Municipality)</label>
                        <input name="deceased_residence" type="text" class="form-control"
                            value="{{ old('deceased_residence', $detail->deceased_residence ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sex</label>
                        <select name="deceased_sex" class="form-select" required>
                            <option value="">--Select--</option>
                            <option value="M" {{ old('deceased_sex', $detail->deceased_sex ?? '') == 'M' ? 'selected' : '' }}>Male</option>
                            <option value="F" {{ old('deceased_sex', $detail->deceased_sex ?? '') == 'F' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Civil Status</label>
                        <select name="deceased_civil_status" class="form-select" required>
                            <option value="">--Select--</option>
                            <option value="Single"   {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married"  {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Widow"    {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Widow' ? 'selected' : '' }}>Widow</option>
                            <option value="Widower"  {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Widower' ? 'selected' : '' }}>Widower</option>
                            <option value="Annulled" {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Annulled' ? 'selected' : '' }}>Annulled</option>
                            <option value="Divorced" {{ old('deceased_civil_status', $detail->deceased_civil_status ?? '') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Birthday</label>
                        <input name="deceased_birthday" type="date" class="form-control"
                            value="{{ old('deceased_birthday', $detail->deceased_birthday ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Age</label>
                        <input name="deceased_age" type="number" class="form-control"
                            value="{{ old('deceased_age', $detail->deceased_age ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Death</label>
                        <input name="deceased_date_of_death" type="date" class="form-control"
                            value="{{ old('deceased_date_of_death', $detail->deceased_date_of_death ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Religion</label>
                        <input name="deceased_religion" type="text" class="form-control"
                            value="{{ old('deceased_religion', $detail->deceased_religion ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Occupation</label>
                        <input name="deceased_occupation" type="text" class="form-control"
                            value="{{ old('deceased_occupation', $detail->deceased_occupation ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Citizenship</label>
                        <input name="deceased_citizenship" type="text" class="form-control"
                            value="{{ old('deceased_citizenship', $detail->deceased_citizenship ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time of Death</label>
                        <input name="deceased_time_of_death" type="text" class="form-control"
                            value="{{ old('deceased_time_of_death', $detail->deceased_time_of_death ?? '') }}" placeholder='eg.10:30 PM'>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cause of Death</label>
                        <input name="deceased_cause_of_death" type="text" class="form-control"
                            value="{{ old('deceased_cause_of_death', $detail->deceased_cause_of_death ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Place of Death</label>
                        <input name="deceased_place_of_death" type="text" class="form-control"
                            value="{{ old('deceased_place_of_death', $detail->deceased_place_of_death ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Father's Name</label>
                        <div class="row g-2">
                            <div class="col"><input name="deceased_father_first_name" type="text" class="form-control" placeholder="First" value="{{ old('deceased_father_first_name', $detail->deceased_father_first_name ?? '') }}"></div>
                            <div class="col"><input name="deceased_father_middle_name" type="text" class="form-control" placeholder="Middle" value="{{ old('deceased_father_middle_name', $detail->deceased_father_middle_name ?? '') }}"></div>
                            <div class="col"><input name="deceased_father_last_name" type="text" class="form-control" placeholder="Last" value="{{ old('deceased_father_last_name', $detail->deceased_father_last_name ?? '') }}"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mother's Maiden Name</label>
                        <div class="row g-2">
                            <div class="col"><input name="deceased_mother_first_name" type="text" class="form-control" placeholder="First" value="{{ old('deceased_mother_first_name', $detail->deceased_mother_first_name ?? '') }}"></div>
                            <div class="col"><input name="deceased_mother_middle_name" type="text" class="form-control" placeholder="Middle" value="{{ old('deceased_mother_middle_name', $detail->deceased_mother_middle_name ?? '') }}"></div>
                            <div class="col"><input name="deceased_mother_last_name" type="text" class="form-control" placeholder="Last" value="{{ old('deceased_mother_last_name', $detail->deceased_mother_last_name ?? '') }}"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Corpse Disposal</label>
                        <input name="corpse_disposal" type="text" class="form-control"
                            value="{{ old('corpse_disposal', $detail->corpse_disposal ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Interment/Cremation</label>
                        <input name="interment_cremation_date" type="date" class="form-control"
                            value="{{ old('interment_cremation_date', $detail->interment_cremation_date ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time</label>
                        <input name="interment_cremation_time" type="text" class="form-control"
                            value="{{ old('interment_cremation_time', $detail->interment_cremation_time ?? '') }}" placeholder='e.g 8:00 AM'>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cemetery / Crematory</label>
                        <input name="cemetery_or_crematory" type="text" class="form-control"
                            value="{{ old('cemetery_or_crematory', $detail->cemetery_or_crematory ?? '') }}">
                    </div>
                </div>
            </div>

{{-- 2. DOCUMENTS --}}
<div class="card mb-4 border-0">
    <div class="card-header fw-semibold bg-light">B. Documents</div>
    <div class="card-body row g-3">

        {{-- Death Certificate --}}
        <div class="col-md-4">
            <label class="form-label">Death Certificate Registration No.</label>
            <input name="death_cert_registration_no" type="text" class="form-control"
                value="{{ old('death_cert_registration_no', $detail->death_cert_registration_no ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Released To (Name & Signature)</label>
            <input name="death_cert_released_to" type="text" class="form-control mb-2"
                value="{{ old('death_cert_released_to', $detail->death_cert_released_to ?? '') }}">
            <div class="border rounded bg-light p-2 mb-2 position-relative">
                <canvas id="death-cert-signature-pad" width="250" height="80" style="background: #fff;"></canvas>
                <button type="button" id="clear-death-cert-signature" class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2">Clear</button>
            </div>
            <input type="hidden" name="death_cert_released_signature" id="death_cert_released_signature" value="{{ old('death_cert_released_signature', $detail->death_cert_released_signature ?? '') }}">
            @if(!empty($detail->death_cert_released_signature))
                <div><img src="{{ $detail->death_cert_released_signature }}" alt="Signature" style="max-width:160px"></div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Release Date</label>
            <input name="death_cert_released_date" type="date" class="form-control"
                value="{{ old('death_cert_released_date', $detail->death_cert_released_date ?? '') }}">
        </div>

        {{-- Funeral Contract --}}
        <div class="col-md-4">
            <label class="form-label">Funeral Contract No.</label>
            <input name="funeral_contract_no" type="text" class="form-control"
                value="{{ old('funeral_contract_no', $detail->funeral_contract_no ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Released To (Name & Signature)</label>
            <input name="funeral_contract_released_to" type="text" class="form-control mb-2"
                value="{{ old('funeral_contract_released_to', $detail->funeral_contract_released_to ?? '') }}">
            <div class="border rounded bg-light p-2 mb-2 position-relative">
                <canvas id="funeral-contract-signature-pad" width="250" height="80" style="background: #fff;"></canvas>
                <button type="button" id="clear-funeral-contract-signature" class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2">Clear</button>
            </div>
            <input type="hidden" name="funeral_contract_released_signature" id="funeral_contract_released_signature" value="{{ old('funeral_contract_released_signature', $detail->funeral_contract_released_signature ?? '') }}">
            @if(!empty($detail->funeral_contract_released_signature))
                <div><img src="{{ $detail->funeral_contract_released_signature }}" alt="Signature" style="max-width:160px"></div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Release Date</label>
            <input name="funeral_contract_released_date" type="date" class="form-control"
                value="{{ old('funeral_contract_released_date', $detail->funeral_contract_released_date ?? '') }}">
        </div>

        {{-- Official Receipt --}}
        <div class="col-md-4">
            <label class="form-label">Official Receipt No.</label>
            <input name="official_receipt_no" type="text" class="form-control"
                value="{{ old('official_receipt_no', $detail->official_receipt_no ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Released To (Name & Signature)</label>
            <input name="official_receipt_released_to" type="text" class="form-control mb-2"
                value="{{ old('official_receipt_released_to', $detail->official_receipt_released_to ?? '') }}">
            <div class="border rounded bg-light p-2 mb-2 position-relative">
                <canvas id="official-receipt-signature-pad" width="250" height="80" style="background: #fff;"></canvas>
                <button type="button" id="clear-official-receipt-signature" class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2">Clear</button>
            </div>
            <input type="hidden" name="official_receipt_released_signature" id="official_receipt_released_signature" value="{{ old('official_receipt_released_signature', $detail->official_receipt_released_signature ?? '') }}">
            @if(!empty($detail->official_receipt_released_signature))
                <div><img src="{{ $detail->official_receipt_released_signature }}" alt="Signature" style="max-width:160px"></div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Release Date</label>
            <input name="official_receipt_released_date" type="date" class="form-control"
                value="{{ old('official_receipt_released_date', $detail->official_receipt_released_date ?? '') }}">
        </div>

    </div>
</div>


            {{-- 3. INFORMANT DETAILS --}}
            <div class="card mb-4 border-0">
                <div class="card-header fw-semibold bg-light">C. Informant Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input name="informant_name" type="text" class="form-control"
                            value="{{ old('informant_name', $detail->informant_name ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Age</label>
                        <input name="informant_age" type="number" class="form-control"
                            value="{{ old('informant_age', $detail->informant_age ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Civil Status</label>
                        <input name="informant_civil_status" type="text" class="form-control"
                            value="{{ old('informant_civil_status', $detail->informant_civil_status ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship to Deceased</label>
                        <input name="informant_relationship" type="text" class="form-control"
                            value="{{ old('informant_relationship', $detail->informant_relationship ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact No.</label>
                        <input name="informant_contact_no" type="text" class="form-control"
                            value="{{ old('informant_contact_no', $detail->informant_contact_no ?? '') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <input name="informant_address" type="text" class="form-control"
                            value="{{ old('informant_address', $detail->informant_address ?? '') }}">
                    </div>
                </div>
            </div>

@php
    $packageName = $booking->package->name ?? '';
    $totalAmount = ($booking->customized_package_id && $booking->customizedPackage)
        ? ($booking->customizedPackage->custom_total_price ?? 0)
        : ($booking->package->total_price ?? 0);

    $feeAmount = floatval(old('amount', $detail->amount ?? $totalAmount));
    $otherFee = floatval(old('other_fee', $detail->other_fee ?? 0));
    $grandTotal = $feeAmount + $otherFee;
@endphp

            {{-- 4. SERVICE, AMOUNT, FEES --}}
            <div class="card mb-4 border-0">
                <div class="card-header fw-semibold bg-light">D. Service and Payment</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Service</label>
                        <input name="service" type="text" class="form-control"
                            value="{{ old('service', $packageName) }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input name="amount" id="amount" type="number" step="0.01" class="form-control"
                            value="{{ old('amount', $totalAmount) }}" readonly>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Other Fee</label>
                        <input name="other_fee" id="other_fee" type="number" step="0.01" min="0" class="form-control"
                            value="{{ old('other_fee', $detail->other_fee ?? '') }}" readonly>
                    </div>


                    <div class="col-md-2">
                        <label class="form-label">Deposit</label>
                        <input name="deposit" type="text" class="form-control"
                            value="{{ old('deposit', $detail->deposit ?? '') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">CSWD</label>
                        <input name="cswd" type="text" class="form-control"
                            value="{{ old('cswd', $detail->cswd ?? '') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">DSWD</label>
                        <input name="dswd" type="text" class="form-control"
                            value="{{ old('dswd', $detail->dswd ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" readonly>{{ old('remarks', $detail->remarks ?? '') }}</textarea>
                    </div>
                </div>
            </div>

{{-- 5. CERTIFICATION (Paragraph style) --}}
<div class="card mb-4 border-0">
    <div class="card-header fw-semibold bg-light">E. Certification</div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <b>Instructions:</b> Please complete the certification details as a paragraph below.
        </div>
        <div class="mb-4">
            <span>I,</span>
            <input name="certifier_name" type="text" class="form-control d-inline-block w-auto mx-2 mb-2"
                   style="min-width:180px;max-width:220px;"
                   value="{{ old('certifier_name', $detail->certifier_name ?? '') }}"
                   placeholder="Full Name" required>
            <span>, the</span>
            <input name="certifier_relationship" type="text" class="form-control d-inline-block w-auto mx-2 mb-2"
                   style="min-width:130px;max-width:160px;"
                   value="{{ old('certifier_relationship', $detail->certifier_relationship ?? '') }}"
                   placeholder="Relationship" required>
            <span>of the late indicated above with residence at</span>
            <input name="certifier_residence" type="text" class="form-control d-inline-block w-auto mx-2 mb-2"
                   style="min-width:220px;max-width:270px;"
                   value="{{ old('certifier_residence', $detail->certifier_residence ?? '') }}"
                   placeholder="Residence" required>
            <span>hereby certify that the above information supplied by me are true and correct.</span>
        </div>
        <div class="mb-4">
            <span>I also agree to pay the amount of</span>
            <input name="certifier_amount" type="text" class="form-control d-inline-block w-auto mx-2 mb-2"
                   style="min-width:170px;max-width:200px;"
                   value="{{ old('certifier_amount', $detail->certifier_amount ?? '') }}"
                   placeholder="Amount in words" required>
            <span>(Total: ₱<span id="grandTotal">{{ number_format($grandTotal, 2) }}</span>)
            in full before the day of interment/cremation of the deceased.</span>
        </div>
        <div class="mb-2">
            <strong>Signature over printed name:</strong>
        </div>
        <div class="row">
            <div class="col-md-6 mb-2">
                <input name="certifier_signature" type="text" class="form-control mb-2"
                       value="{{ old('certifier_signature', $detail->certifier_signature ?? '') }}"
                       placeholder="Type name (printed signature)">
                <div class="border rounded bg-light p-2 mb-2 position-relative">
                    <canvas id="certifier-signature-pad" width="250" height="80" style="background: #fff;"></canvas>
                    <button type="button" id="clear-certifier-signature" class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2">Clear</button>
                </div>
                <input type="hidden" name="certifier_signature_image" id="certifier_signature_image" value="{{ old('certifier_signature_image', $detail->certifier_signature_image ?? '') }}">
                @if(!empty($detail->certifier_signature_image))
                    <div><img src="{{ $detail->certifier_signature_image }}" alt="Signature" style="max-width:160px"></div>
                @endif
            </div>
        </div>
    </div>
</div>



            {{-- SUBMIT --}}
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-check2-circle"></i> Submit
                </button>
            </div>
        </form>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountField = document.getElementById('amount');
    const otherFeeField = document.getElementById('other_fee');
    const grandTotalSpan = document.getElementById('grandTotal');

    function updateTotal() {
        let amount = parseFloat(amountField.value) || 0;
        let otherFee = parseFloat(otherFeeField.value) || 0;
        let total = amount + otherFee;
        grandTotalSpan.textContent = total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    otherFeeField.addEventListener('input', updateTotal);
    amountField.addEventListener('input', updateTotal);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.6/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function setupPad(canvasId, clearBtnId, hiddenInputId, oldImage) {
        const canvas = document.getElementById(canvasId);
        const clearBtn = document.getElementById(clearBtnId);
        const hiddenInput = document.getElementById(hiddenInputId);
        if (!canvas || !clearBtn || !hiddenInput) return;
        const signaturePad = new SignaturePad(canvas);

        // Load old image if exists
        if (oldImage) signaturePad.fromDataURL(oldImage);

        // Clear button logic
        clearBtn.addEventListener('click', function () {
            signaturePad.clear();
            hiddenInput.value = '';
        });

        // On form submit, put image data in hidden input
        canvas.closest('form').addEventListener('submit', function (e) {
            if (!signaturePad.isEmpty()) {
                hiddenInput.value = signaturePad.toDataURL();
            }
        });
    }

    setupPad(
        'death-cert-signature-pad',
        'clear-death-cert-signature',
        'death_cert_released_signature',
        @json(old('death_cert_released_signature', $detail->death_cert_released_signature ?? null))
    );
    setupPad(
        'funeral-contract-signature-pad',
        'clear-funeral-contract-signature',
        'funeral_contract_released_signature',
        @json(old('funeral_contract_released_signature', $detail->funeral_contract_released_signature ?? null))
    );
    setupPad(
        'official-receipt-signature-pad',
        'clear-official-receipt-signature',
        'official_receipt_released_signature',
        @json(old('official_receipt_released_signature', $detail->official_receipt_released_signature ?? null))
    );
    setupPad(
        'certifier-signature-pad',
        'clear-certifier-signature',
        'certifier_signature_image',
        @json(old('certifier_signature_image', $detail->certifier_signature_image ?? null))
    );
});
</script>
</x-client-layout>
