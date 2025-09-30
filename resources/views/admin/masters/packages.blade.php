@extends('layouts.app')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="card-title">Packages</h4>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div>
                    <form method="GET" class="mb-3 d-flex flex-wrap gap-2 align-items-center">

                        {{-- Limit dropdown --}}
                        <select name="limit" class="form-control w-auto" onchange="this.form.submit()">
                            <option value="15" {{ $limit == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                        </select>

                        {{-- Country/Region dropdown --}}
                        <select name="location_id" class="form-control w-auto" onchange="this.form.submit()">
                            <option value="">All COUNTRIES/REGIONS</option>
                            @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ strtoupper($location->name) }}
                            </option>
                            @endforeach
                        </select>

                        {{-- Operator dropdown --}}
                        <select name="operator_id" class="form-control w-auto" onchange="this.form.submit()">
                            <option value="">ALL OPERATOR</option>
                            @foreach($operators as $operator)
                            <option value="{{ $operator->id }}" {{ request('operator_id') == $operator->id ? 'selected' : '' }}>
                                {{ strtoupper($operator->name) }}
                            </option>
                            @endforeach
                        </select>

                        {{-- Is Unlimited dropdown --}}
                        <select name="is_unlimited" class="form-control w-auto" onchange="this.form.submit()">
                            <option value="">UNLIMITED?</option>
                            <option value="1" {{ request('is_unlimited') == '1' ? 'selected' : '' }}>YES</option>
                            <option value="0" {{ request('is_unlimited') == '0' ? 'selected' : '' }}>NO</option>
                        </select>

                    </form>

                </div>

                <div class="table-responsive">
                    <table class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID#</th>
                                <th>Country/Region</th>
                                <th>Operator</th>
                                <th>Airalo Package ID</th>
                                <th>Package Name</th>
                                <th>Validity Days</th>
                                <th>Unlimited</th>
                                <th>Data</th>
                                <th>Short Info</th>
                                <th>Status</th>
                                <th>Popular</th>
                                <th>Recommend</th>
                                <th>Best Value</th>
                                <!-- <th>Action</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packages as $i => $package)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                @if($package->operator->type == 'local' )
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ asset($package->country->image ?? '') }}"
                                            alt="Flag"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <span>{{ $package->country->name }}</span>
                                    </div>
                                </td>
                                @else
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ asset($package->region->image ?? '') }}"
                                            alt="Flag"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <span>{{ $package->region->name }}</span>
                                    </div>
                                </td>
                                @endif
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="{{ asset($package->operator->image ?? '') }}"
                                            alt="Flag"
                                            style="width: 65px; height: 45px; object-fit: cover;">
                                        <span>{{ $package->operator->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $package->airalo_package_id }}</td>
                                <td>{{ $package->name }}</td>
                                <td>{{ $package->day }}</td>
                                <td>{{ $package->is_unlimited == 1 ? 'Yes' : 'No' }}</td>
                                <td>{{ $package->data }}</td>
                                <td>{{ $package->short_info ?? '---' }}</td>
                                <td>
                                    <div class="form-check form-switch align-items-center justify-content-center">
                                        <input class="form-check-input package-toggle custom-switch"
                                            data-id="{{ $package->id }}"
                                            data-field="is_active"
                                            {{ $package->is_active ? 'checked' : '' }}
                                            type="checkbox" role="switch">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch align-items-center justify-content-center">
                                        <input class="form-check-input package-toggle custom-switch"
                                            data-id="{{ $package->id }}"
                                            data-field="is_popular"
                                            {{ $package->is_popular ? 'checked' : '' }}
                                            type="checkbox" role="switch">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch align-items-center justify-content-center">
                                        <input class="form-check-input package-toggle custom-switch"
                                            data-id="{{ $package->id }}"
                                            data-field="is_recommend"
                                            {{ $package->is_recommend ? 'checked' : '' }}
                                            type="checkbox" role="switch">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch align-items-center justify-content-center">
                                        <input class="form-check-input package-toggle custom-switch"
                                            data-id="{{ $package->id }}"
                                            data-field="is_best_value"
                                            {{ $package->is_best_value ? 'checked' : '' }}
                                            type="checkbox" role="switch">
                                    </div>
                                </td>

                                <!-- <td><button class="btn btn-sm btn-warning">View</button></td> -->
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-center mt-4 float-end">
                    {{ $packages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        this.form.submit();
    });
</script>

@endsection

@push('scripts')
<script>
    $(document).on('change', '.package-toggle', function() {
        let packageId = $(this).data('id');
        let field = $(this).data('field');
        let value = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: "{{ route('admin.package.update') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: packageId,
                field: field,
                value: value
            },
            success: function(response) {
                toastr.success(response.message);
            },
            error: function(xhr) {
                toastr.error("Something went wrong. Please try again.");
            }
        });
    });
</script>

@endpush
