@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="fw-bold mb-2 text-white">Dashboard Overview</h1>
            <p class="mb-0 text-white-50">Welcome back! Here's what's happening with your eSIM platform today.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-white-50 text-sm">
                <i class="fas fa-calendar-alt me-2"></i>
                {{ date('M d, Y') }}
            </div>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.user.index') }}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="number">{{ number_format($totalUser) }}</div>
                <div class="label">Total Users</div>
                <div class="trend text-success">
                    <i class="fas fa-arrow-up me-1"></i>
                    <span class="font-medium">+12%</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stats-card">
            <div class="icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <i class="fas fa-sim-card"></i>
            </div>
            <div class="number">{{ number_format($totalActiveEsim) }}</div>
            <div class="label">Active eSIMs</div>
            <div class="trend text-success">
                <i class="fas fa-arrow-up me-1"></i>
                <span class="font-medium">+8%</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.orders') }}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="number">{{ number_format($totalCompleteOrder) }}</div>
                <div class="label">Completed Orders</div>
                <div class="trend text-success">
                    <i class="fas fa-arrow-up me-1"></i>
                    <span class="font-medium">+15%</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.orders') }}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="number">{{ number_format($totalOrder - $totalCompleteOrder) }}</div>
                <div class="label">Failed Orders</div>
                <div class="trend text-danger">
                    <i class="fas fa-arrow-down me-1"></i>
                    <span class="font-medium">-5%</span>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mt-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.esims') }}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="number">{{ number_format($totalEsim) }}</div>
                <div class="label">Total eSIMs</div>
                <div class="trend text-info">
                    <i class="fas fa-arrow-right me-1"></i>
                    <span class="font-medium">Stable</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.user.kyc.index','pending')}}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="number">{{ $pendingKyc }}</div>
                <div class="label">Pending KYC</div>
                <div class="trend text-warning">
                    <i class="fas fa-clock me-1"></i>
                    <span class="font-medium">Review</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.user.kyc.index','approved')}}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="number">{{ $approvedKyc }}</div>
                <div class="label">Approved KYC</div>
                <div class="trend text-success">
                    <i class="fas fa-thumbs-up me-1"></i>
                    <span class="font-medium">Verified</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.user.kyc.index','rejected')}}" class="text-decoration-none">
            <div class="stats-card">
                <div class="icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="number">{{ $rejectedKyc }}</div>
                <div class="label">Rejected KYC</div>
                <div class="trend text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <span class="font-medium">Issues</span>
                </div>
            </div>
        </a>
    </div>
</div>



<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Orders</div>
                <select id="OrderChartFilter" class="form-control w-auto">
                    <option value="week" selected>This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="orderBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>


    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Users</div>
                <select id="userChartFilter" class="form-control w-auto">
                    <option value="week">Last 7 Days</option>
                    <option value="month">Monthly</option>
                    <option value="year">Yearly</option>
                </select>
            </div>

            <div class="card-body">
                <div class="chart-container">
                    <canvas id="UserbarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-round">
            <div class="card-body">
                <div class="card-head-row card-tools-still-right">
                    <div class="card-title">New Customers</div>
                </div>
                <div class="card-list py-4">
                    @foreach($latestUsers as $user)
                    <div class="item-list">
                        <div class="avatar">
                            <img
                                src="{{ $user->image ? asset($user->image) : asset('assets/defaultProfile.png') }}"
                                alt="..."
                                class="avatar-img rounded-circle" />
                        </div>
                        <div class="info-user ms-3">
                            <div class="username">{{$user->name ?? $user->email}}</div>
                            <div class="status">{{date('d M Y h:i A',strtotime($user->created_at))}}</div>
                        </div>
                    </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-round">
            <div class="card-header">
                <div class="card-head-row card-tools-still-right">
                    <div class="card-title">Transaction History</div>

                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-striped table-hover align-middle mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th scope="col">Payment</th>
                                <th scope="col" class="text-end">Date & Time</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tran)
                            <tr>
                                <!-- Payment Info -->
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                            style="width:35px;height:35px;">
                                            <i class="fa fa-credit-card"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted">{{$tran->payment_id}}</div>
                                            @if($tran->user)
                                            <div class="fw-bold text-dark">
                                                👤 <a href="{{route('admin.user.details',$tran->user->id)}}"
                                                    class="text-decoration-none">

                                                    {{$tran->user->name ?? $tran->user->email}}

                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="text-end text-muted">
                                    <i class="fa fa-calendar-alt me-1"></i>
                                    {{$tran->updated_at->format('d M Y, h:i a')}}
                                </td>

                                <!-- Amount -->
                                <td class="text-end fw-bold">
                                    {{$tran->currency->symbol}} {{$tran->amount}}
                                </td>

                                <!-- Status -->
                                <td class="text-end">
                                    @if($tran->payment_status == 'paid')
                                    <span class="badge badge-success px-3 py-2">
                                        <i class="fa fa-check-circle me-1"></i> Paid
                                    </span>
                                    @else
                                    <span class="badge badge-warning text-dark px-3 py-2">
                                        <i class="fa fa-hourglass-half me-1"></i> Created
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fa fa-info-circle me-1"></i> No transactions found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="card-title">Top Destination</h4>
                <p class="card-category">
                    Esim Distribution by Region and Country
                </p>
                <div class="mt-3">
                    <select id="mapFilter" class="form-select w-auto mx-auto">
                        <option value="week">Last 7 Days</option>
                        <option value="month" selected>This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="col-md-10 ms-auto me-auto">
                    <div class="mapcontainer">
                        <div id="world-map" class="w-100" style="height: 450px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- jsvectormap -->
<script src="{{asset('assets/js/plugin/jsvectormap/jsvectormap.min.js')}}"></script>
<script src="{{asset('assets/js/plugin/jsvectormap/world.js')}}"></script>
<script>
    $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#177dff",
        fillColor: "rgba(23, 125, 255, 0.14)",
    });

    $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#f3545d",
        fillColor: "rgba(243, 84, 93, .14)",
    });

    $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#ffa534",
        fillColor: "rgba(255, 165, 52, .14)",
    });

    let orderctx = document.getElementById("orderBarChart").getContext("2d");

    // Datasets from Laravel
    let datasets = {
        week: {
            labels: @json($weekLabels),
            data: @json($weekData)
        },
        month: {
            labels: @json($monthLabels),
            data: @json($monthData)
        },
        year: {
            labels: @json($yearLabels),
            data: @json($yearData)
        }
    };

    function buildChart(labels, data) {
        return new Chart(orderctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                        label: "Completed",
                        backgroundColor: "#59d05d",
                        data: data.completed
                    },
                    {
                        label: "Cancelled",
                        backgroundColor: "#177dff",
                        data: data.cancelled
                    },
                    {
                        label: "Failed",
                        backgroundColor: "#ff4d4d",
                        data: data.failed
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: "bottom"
                },
                title: {
                    display: true,
                    text: "Orders by Status"
                },
                tooltips: {
                    mode: "index",
                    intersect: false
                },
                scales: {
                    xAxes: [{
                        stacked: true
                    }],
                    yAxes: [{
                        stacked: true
                    }]
                }
            }
        });
    }

    // Initial chart
    let orderChart = buildChart(datasets.week.labels, datasets.week.data);

    // On dropdown change
    document.getElementById("OrderChartFilter").addEventListener("change", function() {
        orderChart.destroy();
        let selected = this.value;
        orderChart = buildChart(datasets[selected].labels, datasets[selected].data);
    });


    // Laravel data injection
    const datasetsUser = {
        week: @json($userFilterWeek),
        month: @json($userFilterMonth),
        year: @json($userFilterYear)
    };

    const ctxUser = document.getElementById("UserbarChart").getContext("2d");

    function buildUserChart(labels, data) {
        return new Chart(ctxUser, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "User Registrations",
                    backgroundColor: "rgb(23, 125, 255)",
                    borderColor: "rgb(23, 125, 255)",
                    borderWidth: 1,
                    data: data
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Initial load
    let userChart = buildUserChart(
        datasetsUser.week.labels,
        datasetsUser.week.data
    );

    // Filter change event
    document.getElementById("userChartFilter").addEventListener("change", function() {
        const selected = this.value;
        userChart.destroy();
        userChart = buildUserChart(
            datasetsUser[selected].labels,
            datasetsUser[selected].data
        );
    });
</script>

<script>
    var mapdatasets = @json($mapData);
    var currentMap;

    function buildMap(data) {
        return new jsVectorMap({
            selector: "#world-map",
            map: "world",
            zoomOnScroll: false,
            regionStyle: {
                hover: {
                    fill: "#435ebe"
                },
                initial: {
                    fill: "#c8d6e5"
                }
            },
            series: {
                regions: [{
                    values: data,
                    scale: ['#dfe6e9', '#2d3436'],
                    normalizeFunction: 'polynomial'
                }]
            },
            onRegionTooltipShow(event, tooltip, code) {
                if (data[code]) {
                    tooltip.text(tooltip.text() + ' — ' + data[code] + ' Orders');
                }
            }
        });
    }

    // Initial map load
    currentMap = buildMap(mapdatasets['month']);

    // Change filter
    document.getElementById("mapFilter").addEventListener("change", function() {
        currentMap.destroy();
        let selected = this.value;
        currentMap = buildMap(mapdatasets[selected]);
    });
</script>
@endpush
