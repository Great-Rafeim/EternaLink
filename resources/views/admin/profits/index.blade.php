<x-admin-layout>
    <div class="container py-4">
        <h2 class="mb-4 fw-bold" style="color:#1565c0;">
            <i class="bi bi-currency-dollar me-2"></i> Profits & Package Overview
        </h2>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg,#007bff 0,#43cea2 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-uppercase fw-semibold text-white-50 small mb-1">Paid Packages</div>
                                <div class="display-6 fw-bold text-white">{{ $totalPaid }}</div>
                                <div class="text-white-50 small mt-1">Successful Transactions</div>
                            </div>
                            <span class="ms-2"><i class="bi bi-bag-check-fill display-4 text-white opacity-75"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg,#ff9800 0,#ffc107 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-uppercase fw-semibold text-white-50 small mb-1">Pending Payments</div>
                                <div class="display-6 fw-bold text-white">{{ $totalPending }}</div>
                                <div class="text-white-50 small mt-1">Awaiting Completion</div>
                            </div>
                            <span class="ms-2"><i class="bi bi-clock-history display-4 text-white opacity-75"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg,#673ab7 0,#512da8 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-uppercase fw-semibold text-white-50 small mb-1">Total Profit</div>
                                <div class="display-6 fw-bold text-white">₱{{ number_format($totalProfit, 2) }}</div>
                                <div class="text-white-50 small mt-1">All Paid Convenience Fees</div>
                            </div>
                            <span class="ms-2"><i class="bi bi-cash-coin display-4 text-white opacity-75"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg,#26a69a 0,#80d8ff 100%);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-uppercase fw-semibold text-white-50 small mb-1">Total Package Value</div>
                                <div class="display-6 fw-bold text-white">
                                    ₱{{ number_format($totalPackageValue ?? 0, 2) }}
                                </div>
                                <div class="text-white-50 small mt-1">Sum of All Package Prices</div>
                            </div>
                            <span class="ms-2"><i class="bi bi-gift-fill display-4 text-white opacity-75"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-table me-1"></i> Package Payment Breakdown
                </h5>
                <div>
                    <input class="form-control d-inline-block w-auto me-2" id="profit-search" placeholder="Search..." style="max-width:180px;display:inline-block;" autocomplete="off">
                    <button class="btn btn-sm btn-success" id="export-csv">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <div class="table-responsive rounded-3 border overflow-hidden">
                <table class="table table-bordered table-sm align-middle mb-0" id="profit-table">
                    <thead class="table-primary text-center">
                        <tr>
                            <th class="sort" data-sort="booking_id"      style="cursor:pointer">Booking ID <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="reference_number" style="cursor:pointer">Reference # <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="funeral_home"    style="cursor:pointer">Funeral Home <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="client"          style="cursor:pointer">Client <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="convenience_fee" style="cursor:pointer">Conv. Fee <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="amount"          style="cursor:pointer">Amount Paid <i class="bi bi-caret-down-fill small"></i></th>
                            <th class="sort" data-sort="paid_at"         style="cursor:pointer">Paid At <i class="bi bi-caret-down-fill small"></i></th>
                        </tr>
                    </thead>
                    <tbody class="list">
                    @foreach($breakdown as $row)
                        <tr>
                            <td class="booking_id text-center">{{ $row['booking_id'] }}</td>
                            <td class="reference_number text-center">{{ $row['reference_number'] ?? '—' }}</td>
                            <td class="funeral_home">{{ $row['funeral_home'] }}</td>
                            <td class="client">{{ $row['client'] }}</td>
                            <td class="convenience_fee text-end">₱{{ number_format($row['convenience_fee'],2) }}</td>
                            <td class="amount text-end">₱{{ number_format($row['amount'],2) }}</td>
                            <td class="paid_at text-center">
                                {{ $row['paid_at'] ? \Carbon\Carbon::parse($row['paid_at'])->format('Y-m-d H:i') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    @if(count($breakdown) == 0)
                        <tr>
                            <td colspan="7" class="text-center text-muted">No payments found.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- List.js for search/sort --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
    <script>
        // Init List.js for search & sorting
        const profitList = new List('profit-table', {
            valueNames: [
                'booking_id', 'reference_number', 'funeral_home', 'client', 'convenience_fee', 'amount', 'paid_at'
            ],
            listClass: 'list'
        });
        document.getElementById('profit-search').addEventListener('keyup', function() {
            profitList.search(this.value);
        });

        // Export CSV (visible rows only)
        document.getElementById('export-csv').addEventListener('click', function() {
            let rows = Array.from(document.querySelectorAll('#profit-table tbody.list tr')).filter(row => row.offsetParent !== null);
            let csv = [];
            let headers = Array.from(document.querySelectorAll('#profit-table thead th')).map(th => th.innerText.replace(/\s+/g, ' ').trim());
            csv.push(headers.join(','));
            rows.forEach(row => {
                let cells = Array.from(row.querySelectorAll('td'));
                let data = cells.map(td => {
                    // Remove Peso sign for export, and wrap text in quotes
                    let val = td.innerText.replace(/^₱/, '').replace(/"/g, '""');
                    return `"${val}"`;
                });
                csv.push(data.join(','));
            });
            let csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
            let link = document.createElement("a");
            link.setAttribute("href", encodeURI(csvContent));
            link.setAttribute("download", "payments-export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
</x-admin-layout>
