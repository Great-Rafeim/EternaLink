<x-layouts.funeral>
    <div class="container py-5">
        <h2 class="fw-bold mb-4">Customization Request for Booking #{{ $booking->id }}</h2>
        <div class="card mb-4 shadow-lg border-0">
            <div class="card-body">
                <h4 class="mb-3">
                    Client: <span class="fw-normal text-primary">{{ $booking->client->name }}</span>
                </h4>
                <h5 class="mb-4">
                    Package: <span class="fw-normal">{{ $booking->package->name }}</span>
                </h5>
                <hr class="my-4">

                <div class="row g-4">
                    {{-- Customized Items Table --}}
{{-- Customized Items Table --}}
<div class="col-lg-6">
    <div class="p-3 rounded-4 shadow-sm border border-success bg-success bg-opacity-10 h-100">
        <div class="d-flex align-items-center mb-2 gap-2">
            <i class="bi bi-pencil-square text-success"></i>
            <h5 class="mb-0 fw-semibold text-success">Customized Package</h5>
        </div>
        <table class="table table-sm table-bordered align-middle bg-white mb-3">
            <thead class="table-success">
                <tr>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Substitute For</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Asset Category Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $customizedAssetCategoryIds = $customizedPackage->items
                        ->filter(fn($item) => $item->inventoryItem && ($item->inventoryItem->category->is_asset ?? false))
                        ->pluck('inventoryItem.category.id')
                        ->unique()
                        ->toArray();

                    // --- SUM FOR TOTAL: items ---
                    $customizedTotal = 0;
                @endphp
                @foreach($customizedPackage->items as $item)
                    @php
                        $category = $item->inventoryItem->category->name ?? '-';
                        $isAsset = $item->inventoryItem->category->is_asset ?? false;
                        $catId = $item->inventoryItem->category->id ?? null;
                        $original = null;
                        if($item->substitute_for && $item->substitute_for != $item->inventory_item_id){
                            $original = optional($item->substituteFor)->name;
                        }
                        $isSubstituted = $original ? true : false;
                        $assetCat = ($isAsset && $catId)
                            ? $assetCategories->first(fn($ac) => (int)$ac->id === (int)$catId)
                            : null;
                        $lineTotal = $item->unit_price * $item->quantity;
                        $customizedTotal += $lineTotal;
                    @endphp
                    <tr @if($isAsset) class="table-secondary" @elseif($isSubstituted) style="background:#fff9d6;" @endif>
                        <td>
                            {{ $category }}
                            @if($isAsset)
                                <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                            @endif
                        </td>
                        <td>
                            {{ $item->inventoryItem->name ?? '-' }}
                            @if($isSubstituted)
                                <span class="badge bg-warning text-dark ms-1">Substituted</span>
                            @endif
                        </td>
                        <td>
                            @if($original)
                                {{ $original }}
                            @else
                                <span class="text-muted">(Default)</span>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>₱{{ number_format($item->unit_price, 2) }}</td>
                        <td>
                            @if($isAsset && $assetCat && isset($assetCat->price))
                                ₱{{ number_format($assetCat->price, 2) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>₱{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @endforeach

                {{-- Asset categories not yet listed above but required in package --}}
                @php
                    $customizedMissingAssetTotal = 0;
                @endphp
                @foreach($assetCategories as $assetCategory)
                    @if(!in_array($assetCategory->id, $customizedAssetCategoryIds))
                        @php $customizedMissingAssetTotal += $assetCategory->price ?? 0; @endphp
                        <tr class="table-secondary">
                            <td>
                                {{ $assetCategory->name }}
                                <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                            </td>
                            <td colspan="4" class="text-muted">
                                Included as a required asset (exact item to be assigned by parlor)
                            </td>
                            <td>
                                @if(isset($assetCategory->price))
                                    ₱{{ number_format($assetCategory->price, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(isset($assetCategory->price))
                                    ₱{{ number_format($assetCategory->price, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        @php
            $totalBeforeVat = $customizedTotal + $customizedMissingAssetTotal;
            $vatAmount = round($totalBeforeVat * 0.12, 2);
            $totalWithVat = round($totalBeforeVat + $vatAmount, 2);
        @endphp

        <div class="text-end fw-bold">
            <div>
                Subtotal: <span class="text-dark">₱{{ number_format($totalBeforeVat, 2) }}</span>
            </div>
            <div>
                VAT (12%): <span class="text-warning">₱{{ number_format($vatAmount, 2) }}</span>
            </div>
            <div>
                Total (incl. VAT): <span class="text-success">₱{{ number_format($totalWithVat, 2) }}</span>
            </div>
        </div>
    </div>
</div>

                    {{-- End Customized Items Table --}}

                    {{-- Original Package Table --}}
                    <div class="col-lg-6">
                        <div class="p-3 rounded-4 shadow-sm border border-primary bg-primary bg-opacity-10 h-100">
                            <div class="d-flex align-items-center mb-2 gap-2">
                                <i class="bi bi-box text-primary"></i>
                                <h5 class="mb-0 fw-semibold text-primary">Original Package</h5>
                            </div>
                            <table class="table table-sm table-bordered align-middle bg-white mb-3">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Category</th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Asset Category Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $originalAssetCategoryIds = $booking->package->items
                                            ->filter(fn($item) => $item->category->is_asset ?? false)
                                            ->pluck('category.id')
                                            ->unique()
                                            ->toArray();
                                        $originalTotal = 0;
                                    @endphp
                                    @foreach($booking->package->items as $pkgItem)
                                        @php
                                            $isAsset = $pkgItem->category->is_asset ?? false;
                                            $catId = $pkgItem->category->id ?? null;
                                            $qty = $pkgItem->pivot->quantity ?? 1;
                                            $unit = $pkgItem->selling_price ?? 0;
                                            $assetCat = ($isAsset && $catId)
                                                ? $assetCategories->first(fn($ac) => (int)$ac->id === (int)$catId)
                                                : null;
                                            $lineTotal = $qty * $unit;
                                            $originalTotal += $lineTotal;
                                        @endphp
                                        <tr @if($isAsset) class="table-secondary" @endif>
                                            <td>
                                                {{ $pkgItem->category->name ?? '-' }}
                                                @if($isAsset)
                                                    <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                                                @endif
                                            </td>
                                            <td>{{ $pkgItem->name }}</td>
                                            <td>{{ $qty }}</td>
                                            <td>₱{{ number_format($unit, 2) }}</td>
                                            <td>
                                                @if($isAsset && $assetCat && isset($assetCat->price))
                                                    ₱{{ number_format($assetCat->price, 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>₱{{ number_format($lineTotal, 2) }}</td>
                                        </tr>
                                    @endforeach

                                    {{-- Asset categories not yet listed above but required in package --}}
                                    @php
                                        $originalMissingAssetTotal = 0;
                                    @endphp
                                    @foreach($assetCategories as $assetCategory)
                                        @if(!in_array($assetCategory->id, $originalAssetCategoryIds))
                                            @php $originalMissingAssetTotal += $assetCategory->price ?? 0; @endphp
                                            <tr class="table-secondary">
                                                <td>
                                                    {{ $assetCategory->name }}
                                                    <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                                                </td>
                                                <td colspan="3" class="text-muted">
                                                    Included as a required asset (exact item to be assigned by parlor)
                                                </td>
                                                <td>
                                                    @if(isset($assetCategory->price))
                                                        ₱{{ number_format($assetCategory->price, 2) }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(isset($assetCategory->price))
                                                        ₱{{ number_format($assetCategory->price, 2) }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="text-end fw-bold">
                                Total: <span class="text-primary">
                                    ₱{{ number_format($originalTotal + $originalMissingAssetTotal, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- End Original Package Table --}}
                </div>

                <hr>
                <form method="POST" action="{{ route('funeral.bookings.customization.approve', [$booking->id, $customizedPackage->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success px-4 fw-bold">Approve</button>
                </form>
                <form method="POST" action="{{ route('funeral.bookings.customization.deny', [$booking->id, $customizedPackage->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Deny</button>
                </form>
                <a href="{{ route('funeral.bookings.index') }}" class="btn btn-outline-secondary ms-2 px-4">Back to Bookings</a>
            </div>
        </div>
    </div>
</x-layouts.funeral>
