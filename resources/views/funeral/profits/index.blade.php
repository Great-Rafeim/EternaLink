<x-layouts.funeral>
    <style>
        body { background: linear-gradient(120deg, #171c27 60%, #24304b 100%) !important; }
        .profit-header {
            color: #FFF; font-weight: 700; letter-spacing: .5px;
            text-shadow: 0 2px 10px rgba(32,36,60,.12);
        }
        .profit-card {
            border: none;
            border-radius: 1.2rem;
            background: rgba(32,36,60,0.91);
            box-shadow: 0 8px 28px 0 rgba(38, 49, 89, 0.17);
            padding: 2rem 2.4rem 2rem 2.4rem;
            margin-bottom: 1.5rem;
            min-width: 210px;
            transition: transform .12s, box-shadow .12s;
        }
        .profit-card:hover { 
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 36px 0 rgba(80,140,200,0.13);
        }
        .profit-label { font-size: .98rem; color: #b0b6c3; letter-spacing: .5px;}
        .profit-value { font-size: 2.15rem; font-weight: 700; line-height: 1.2; color: #fff;}
        .profit-icon {
            font-size: 2.5rem;
            border-radius: .9rem;
            padding: .6rem .92rem .5rem .92rem;
            margin-right: .9rem;
            background: linear-gradient(135deg,#1a77c2 0%,#39b385 100%);
            color: #fff;
            box-shadow: 0 4px 18px 0 rgba(54,150,200,0.11);
        }
        .bg-blue { background: linear-gradient(135deg,#1a77c2 0%,#3a53c5 100%) !important; }
        .bg-green { background: linear-gradient(135deg,#23a067 0%,#76e6c8 100%) !important;}
        .bg-yellow { background: linear-gradient(135deg,#ffc403 0%,#fff8d1 100%) !important; color: #835500;}
        .chart-card, .breakdown-card {
            background: rgba(32,36,60,0.91);
            border-radius: 1.1rem;
            box-shadow: 0 2px 14px 0 rgba(38,49,89,0.10);
        }
        .breakdown-table th {
            background: #23304e;
            color: #ffd966;
            font-size: .99rem;
            font-weight: 600;
            border-top: none;
            letter-spacing:.04em;
        }
            .breakdown-table thead tr {
        background: #242846 !important;
    }
    .breakdown-table th {
        background: #242846 !important;
        color: #ffd966 !important;
        border: none;
        font-size: 1.03rem;
        font-weight: 700;
        letter-spacing: .04em;
        vertical-align: middle;
        text-align: center;
    }
    .breakdown-table td {
        background: #20233a !important;
        color: #fff !important;
        border-color: #2a2d43;
        font-size: 1.03rem;
        text-align: center;
        vertical-align: middle;
        border-top: 1.5px solid #222546 !important;
        transition: background .12s;
    }
    .breakdown-table tbody tr:hover td {
        background: #232846 !important;
    }
    .breakdown-table tr {
        border-bottom: 1.5px solid #232846;
    }
    .breakdown-table {
        border-radius: 0.8rem;
        overflow: hidden;
    }
    .breakdown-table thead th:first-child {
        border-top-left-radius: 10px;
    }
    .breakdown-table thead th:last-child {
        border-top-right-radius: 10px;
    }
        .breakdown-table td { font-size: 1.03rem; color: #fff; }
        .breakdown-table tbody tr:hover { background: #232f48; }
        .sticky-search { position: sticky; top: 0; background: #23304e; z-index: 2; }
        .form-control:focus { border-color: #6bb6ff; box-shadow: 0 0 0 0.18rem rgba(100,180,255,.13);}
        /* Chart tweaks */
        #profitsChart { max-height:180px!important; }
        /* Responsive table style */
        @media (max-width: 900px) {
            .profit-card, .chart-card, .breakdown-card { padding: 1.2rem !important; }
            .profit-value { font-size: 1.35rem; }
            #profitsChart { max-height:110px!important; }
        }
    </style>

    <div class="container py-4">
        <h2 class="profit-header mb-4" style="font-size:2.1rem;">
            <i class="bi bi-bar-chart-line-fill text-warning me-2"></i> Profit Analytics
        </h2>

        {{-- Top Cards --}}
        <div class="row g-3 mb-2 align-items-stretch">
            <div class="col-md-4">
                <div class="profit-card d-flex align-items-center">
                    <span class="profit-icon bg-blue"><i class="bi bi-archive-fill"></i></span>
                    <div>
                        <div class="profit-label">TOTAL PACKAGE VALUE</div>
                        <div class="profit-value">₱{{ number_format($totalGross, 2) }}</div>
                        <div class="small text-muted">Gross: ₱{{ number_format($totalRawGross, 2) }} (net of 12% VAT)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profit-card d-flex align-items-center">
                    <span class="profit-icon bg-green"><i class="bi bi-cash-stack"></i></span>
                    <div>
                        <div class="profit-label">BOUGHT PACKAGES</div>
                        <div class="profit-value" style="color:#80ffd3">₱{{ number_format($totalPaid, 2) }}</div>
                        <div class="small text-muted">Gross: ₱{{ number_format($totalRawPaid, 2) }} (net of 12% VAT)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profit-card d-flex align-items-center">
                    <span class="profit-icon bg-yellow"><i class="bi bi-bar-chart"></i></span>
                    <div>
                        <div class="profit-label">COMPLETION RATE</div>
                        <div class="profit-value" style="color:#ffd600">{{ number_format($completionRate, 1) }}%</div>
                        <div class="small text-muted">
                            ({{ number_format($totalPaid,2) }}/{{ number_format($totalGross,2) }})
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div class="chart-card mb-4 px-4 py-3">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-graph-up text-success me-2 fs-4"></i>
                <div class="fw-semibold fs-5 text-white">Monthly Paid Package Trend <span class="text-warning">(Net of 12% VAT)</span></div>
            </div>
            <div style="height:150px;width:100%"><canvas id="profitsChart"></canvas></div>
        </div>

{{-- Table Breakdown --}}
<div class="breakdown-card px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-bold fs-5 text-white">
            <i class="bi bi-table me-2"></i>Package Availed Breakdown
        </span>
        <div class="d-flex gap-2">
            <input class="form-control w-auto" id="profit-search" placeholder="Search..." style="max-width:210px;" autocomplete="off">
            <button class="btn btn-success btn-sm" id="export-csv">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover breakdown-table align-middle mb-0" id="profit-table">
            <thead>
                <tr>
                    <th class="sort" data-sort="booking_id" style="cursor:pointer">Booking ID</th>
                    <th class="sort" data-sort="package" style="cursor:pointer">Package</th>
                    <th class="sort" data-sort="client" style="cursor:pointer">Client</th>
                    <th class="sort" data-sort="amount" style="cursor:pointer">Amount Paid (Net)</th>
                    <th class="sort" data-sort="reference_number" style="cursor:pointer">Reference #</th>
                    <th class="sort" data-sort="status" style="cursor:pointer">Status</th>
                    <th class="sort" data-sort="paid_at" style="cursor:pointer">Paid At</th>
                </tr>
            </thead>
            <tbody class="list">
                @foreach($breakdown as $row)
                    <tr>
                        <td class="booking_id">{{ $row['booking_id'] }}</td>
                        <td class="package">{{ $row['package'] ?? '—' }}</td>
                        <td class="client">{{ $row['client'] }}</td>
                        <td class="amount">₱{{ number_format($row['amount'],2) }}</td>
                        <td class="reference_number">{{ $row['reference_number'] ?? '—' }}</td>
                        <td class="status">{{ ucfirst($row['status']) }}</td>
                        <td class="paid_at">
                            {{ $row['paid_at'] ? \Carbon\Carbon::parse($row['paid_at'])->format('Y-m-d H:i') : '—' }}
                        </td>
                    </tr>
                @endforeach
                @if(count($breakdown) == 0)
                    <tr>
                        <td colspan="7" class="text-center text-muted">No paid payments found.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
    </div>


{{-- Chart.js for Line Graph --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
{{-- List.js for search/sort --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>

<script>
    // ---- Chart.js: Monthly Paid Package Trend ----
    (() => {
        const ctx = document.getElementById('profitsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($months),
                datasets: [{
                    label: 'Paid Amount (Net)',
                    data: @json($chartData),
                    fill: true,
                    borderColor: '#59e0a2',
                    backgroundColor: 'rgba(89,224,162,0.13)',
                    tension: 0.33,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#59e0a2'
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#fff' } },
                    y: { beginAtZero: true, ticks: { callback: val => '₱' + val, color: '#fff' }, grid: { color: 'rgba(255,255,255,.06)' } }
                }
            }
        });
    })();

    // ---- List.js: Search & Sorting for Breakdown Table ----
    (() => {
        const profitList = new List('profit-table', {
            valueNames: [
                'booking_id', 'package', 'client', 'amount', 'reference_number', 'status', 'paid_at'
            ],
            listClass: 'list'
        });
        document.getElementById('profit-search').addEventListener('keyup', function() {
            profitList.search(this.value);
        });

        // Export CSV (visible rows only)
        document.getElementById('export-csv')?.addEventListener('click', function() {
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
            link.setAttribute("download", "funeral-payments-export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    })();
</script>

</x-layouts.funeral>
