@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <form method="POST" enctype="multipart/form-data" id="edit-form">
            @csrf
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">General Settings</h4>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fa fa-save me-1"></i> Save Settings
                    </button>
                </div>

                <div class="card-body">
                    {{-- Tabs --}}
                    <ul class="nav nav-pills mb-4" role="tablist">
                        @foreach ($flagGroup as $group)
                        <li class="nav-item">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                data-bs-toggle="pill"
                                data-bs-target="#group{{ $group->id }}"
                                type="button" role="tab">
                                <i class="fa fa-cog me-1"></i> {{ $group->flagGroupName }}
                            </button>
                        </li>
                        @endforeach
                    </ul>

                    {{-- Tab Content --}}
                    <div class="tab-content">
                        @foreach ($flagGroup as $gIndex => $group)
                        <div id="group{{ $group->id }}" class="tab-pane fade {{ $loop->first ? 'show active' : '' }}">

                            {{-- Main Group Flags --}}

                            @foreach ($group->systemFlag->where('valueType', 'PaymentRadio') as $sIndex => $flag)
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <label class="form-label fw-bold">
                                    {{ $flag->displayName }}
                                    @if ($flag->description)
                                    <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                    @endif
                                </label>

                                <input type="hidden" name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][name]" value="{{ $flag->name }}">
                                <input type="hidden" name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][valueType]" value="{{ $flag->valueType }}">

                                <div class="d-flex gap-4 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                            value="Razorpay" {{ $flag->value == 'Razorpay' ? 'checked' : '' }}>
                                        <label class="form-check-label">Razorpay</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                            value="Stripe" {{ $flag->value == 'Stripe' ? 'checked' : '' }}>
                                        <label class="form-check-label">Stripe</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                            value="Cashfree" {{ $flag->value == 'Cashfree' ? 'checked' : '' }}>
                                        <label class="form-check-label">Cashfree</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                            value="GpayInAppPurchase" {{ $flag->value == 'GpayInAppPurchase' ? 'checked' : '' }}>
                                        <label class="form-check-label">Google Pay (In App Purchase)</label>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            {{-- Then render all others --}}
                            @foreach ($group->systemFlag->whereNotIn('valueType',['PaymentRadio','GatewayRadio','PaymentModeSelect']) as $sIndex => $flag)
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <label class="form-label fw-bold">
                                    {{ $flag->displayName }}
                                    @if ($flag->description)
                                    <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                    @endif
                                </label>

                                <input type="hidden" name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][name]" value="{{ $flag->name }}">
                                <input type="hidden" name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][valueType]" value="{{ $flag->valueType }}">

                                @if($flag->valueType == 'timezoneSelect')
                                <select name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]" class="form-control select2">
                                    @foreach (DateTimeZone::listIdentifiers() as $tz)
                                    <option value="{{ $tz }}" {{ $flag->value == $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                    @endforeach
                                </select>

                                @elseif($flag->valueType == 'AiraloSyncDataButton' || $flag->valueType == 'GooglePlayDataButton')
                                <a href="{{route('admin.syncAiralo')}}" class="btn btn-primary">{{$flag->value}}</a>

                                @elseif($flag->valueType == 'Text')
                                <input type="text" class="form-control mt-1"
                                    name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                    value="{{ $flag->value }}">

                                @elseif($flag->valueType == 'Number')
                                <input type="number" class="form-control mt-1"
                                    name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                    value="{{ $flag->value}}">

                                @elseif($flag->valueType == 'File' || $flag->valueType == 'Video')
                                <div class="mt-2">
                                    @if($flag->value)
                                    <div class="mb-2">
                                        @if($flag->valueType == 'File')
                                        <img src="/{{ $flag->value }}" class="img-thumbnail" width="140">
                                        @else
                                        <video width="200" controls>
                                            <source src="/{{ $flag->value }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        @endif
                                        <div class="mt-1">
                                            <small class="text-muted">Current file: {{ basename($flag->value) }}</small>
                                        </div>
                                    </div>
                                    @endif
                                    <input type="file"
                                           class="form-control"
                                           name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value]"
                                           accept="{{ $flag->valueType == 'Video' ? 'video/*' : 'image/*' }}">
                                    <small class="text-muted">
                                        {{ $flag->valueType == 'Video' ? 'Select a video file to replace current one' : 'Select an image file to replace current one' }}
                                    </small>
                                </div>

                                @elseif($flag->valueType == 'MultiSelect')
                                @php $selected = explode(',', $flag->value); @endphp
                                <select name="group[{{ $gIndex }}][systemFlag][{{ $sIndex }}][value][]"
                                    class="form-control select2" multiple>
                                    @foreach($language as $lan)
                                    <option value="{{ $lan->id }}" {{ in_array($lan->id, $selected) ? 'selected' : '' }}>
                                        {{ $lan->languageName }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                            @endforeach

                            {{-- Sub Groups --}}
                            @if($group->subGroup->count())
                            <div class="mt-4">
                                @foreach ($group->subGroup as $sgIndex => $sub)
                                <h5 class="fw-bold text-primary mt-3">{{ $sub->flagGroupName }}
                                     @if ($sub->description)
                                    <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $sub->description }}"></i>
                                    @endif
                                </h5>
                                <hr>
                                @foreach ($sub->systemFlag as $ssIndex => $flag)
                                <input type="hidden"
                                    name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][name]"
                                    value="{{ $flag->name }}">
                                <input type="hidden"
                                    name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][valueType]"
                                    value="{{ $flag->valueType }}">

                                @if($flag->valueType == 'GatewayRadio')
                                <div class="mb-3">
                                    <label class="form-label">{{ $flag->displayName }}
                                         @if ($flag->description)
                                        <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                        @endif
                                    </label>
                                    <div class="d-flex gap-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][value]"
                                                value="1" {{ $flag->value == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label">Active</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][value]"
                                                value="0" {{ $flag->value == 0 ? 'checked' : '' }}>
                                            <label class="form-check-label">InActive</label>
                                        </div>
                                    </div>
                                </div>

                                @elseif($flag->valueType == 'PaymentModeSelect')
                                <div class="mb-3">
                                    <label class="form-label">{{ $flag->displayName }}
                                         @if ($flag->description)
                                        <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                        @endif
                                    </label>
                                    <select class="form-control" name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][value]">
                                        <option value="0" {{$flag->value == 0 ? 'selected' : ''}}>TEST</option>
                                        <option value="1" {{$flag->value == 1 ? 'selected' : ''}}>PRODUCTION</option>
                                    </select>
                                </div>

                                @elseif($flag->valueType == 'File' || $flag->valueType == 'Video')
                                <div class="mb-3">
                                    <label class="form-label">{{ $flag->displayName }}
                                         @if ($flag->description)
                                        <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                        @endif
                                    </label>
                                    <div class="mt-2">
                                        @if($flag->value)
                                        <div class="mb-2">
                                            @if($flag->valueType == 'File')
                                            <img src="/{{ $flag->value }}" class="img-thumbnail" width="140">
                                            @else
                                            <video width="200" controls>
                                                <source src="/{{ $flag->value }}" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                            @endif
                                            <div class="mt-1">
                                                <small class="text-muted">Current file: {{ basename($flag->value) }}</small>
                                            </div>
                                        </div>
                                        @endif
                                        <input type="file"
                                               class="form-control"
                                               name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][value]"
                                               accept="{{ $flag->valueType == 'Video' ? 'video/*' : 'image/*' }}">
                                        <small class="text-muted">
                                            {{ $flag->valueType == 'Video' ? 'Select a video file to replace current one' : 'Select an image file to replace current one' }}
                                        </small>
                                    </div>
                                </div>

                                @else
                                <div class="mb-3">
                                    <label class="form-label">{{ $flag->displayName }}
                                         @if ($flag->description)
                                        <i class="fa fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="{{ $flag->description }}"></i>
                                        @endif
                                    </label>

                                    <input type="{{ strtolower($flag->valueType) == 'number' ? 'number' : 'text' }}"
                                        class="form-control"
                                        name="group[{{ $gIndex }}][subGroup][{{ $sgIndex }}][systemFlag][{{ $ssIndex }}][value]"
                                        value="{{ $flag->value}}">
                                </div>
                                @endif
                                @endforeach
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();

        $('#edit-form').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: "{{ route('admin.updateSystemflag') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    toastr.success('Settings updated successfully!');
                    location.reload()
                },
                error: function(xhr) {
                    toastr.error('Something went wrong.');
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
@endpush
