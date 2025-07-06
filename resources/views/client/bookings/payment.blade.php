<x-client-layout>
    <div class="container py-5">
        <h2 class="fw-bold mb-4" style="color: #1565c0;">
            <i class="bi bi-credit-card-2-front me-2"></i> Pay for Your Booking
        </h2>
        <div class="card shadow-sm mb-4" style="max-width:520px;margin:auto;">
            <div class="card-body px-4 py-4">
                {{-- Show Package Name --}}
                <h4 class="mb-2">
                    <span class="fw-semibold">Package:</span>
                    <span class="text-primary">{{ $booking->package->name ?? 'N/A' }}</span>
                </h4>
                @php
                    $convenienceFee = 25;
                    $vatRate = 0.12;
                    $amount = $booking->final_amount ?? $booking->detail->amount ?? 0;
                    $basePrice = round($amount / (1 + $vatRate), 2);
                    $vatAmount = round($amount - $basePrice, 2);
                    $totalWithFee = $amount + $convenienceFee;
                @endphp

                <div class="mb-3" style="font-size:1.07em;">
                    <div>
                        <strong>Package Price (incl. VAT):</strong>
                        <span class="text-dark">₱{{ number_format($amount, 2) }}</span>
                    </div>
                    <div class="ms-3 mt-1 text-secondary" style="font-size:0.96em;">
                        &bull; Base Price: <span class="fw-semibold">₱{{ number_format($basePrice, 2) }}</span><br>
                        &bull; 12% VAT: <span class="fw-semibold">₱{{ number_format($vatAmount, 2) }}</span>
                    </div>
                    <div class="ms-3 mt-1 text-muted" style="font-size:0.96em;">
                        + ₱{{ number_format($convenienceFee, 2) }} convenience fee (for system usage)
                    </div>
                </div>

                <div class="mb-3 fs-5">
                    <strong>Total to Pay:</strong>
                    <span class="text-success">₱{{ number_format($totalWithFee, 2) }}</span>
                </div>
            </div>

            @if($errors->has('payment'))
                <div class="alert alert-danger mx-4 mb-0">{{ $errors->first('payment') }}</div>
            @endif

            <form method="POST" action="{{ route('client.bookings.pay.link', $booking->id) }}" class="px-4 pb-4">
                @csrf
                <input type="hidden" name="package_amount" value="{{ $amount }}">
                <input type="hidden" name="convenience_fee" value="{{ $convenienceFee }}">
                <input type="hidden" name="total_with_fee" value="{{ $totalWithFee }}">
                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-credit-card"></i> Pay Now
                </button>
            </form>

            <div class="text-center text-muted pb-3" style="font-size: 1em;">
                You will be redirected to a secure PayMongo payment page.<br>
                All major Philippine payment methods accepted.
            </div>
        </div>
    </div>
</x-client-layout>
