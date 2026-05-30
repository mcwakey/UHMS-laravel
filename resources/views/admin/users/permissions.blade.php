@extends('layouts.app')

@section('title', 'User Permissions — ' . $user->name)

@section('content')
<div class="page-wrapper">
    <div class="content">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
            <div>
                <h4 class="fw-bold mb-0">Direct Permissions: <span class="text-primary">{{ $user->name }}</span></h4>
                <p class="text-muted mb-0">
                    Grant emergency one-off permissions in addition to those inherited from the user's role(s).
                    Inherited permissions are shown read-only.
                </p>
            </div>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary btn-md">
                <i class="ti ti-arrow-left me-1"></i>Back to User
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Reason / justification <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="2" class="form-control" required maxlength="500"
                              placeholder="e.g. Covering for absent pharmacist on 2025-11-15. Time-boxed.">{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                @foreach($catalogue as $module => $items)
                <div class="col-xl-4 col-md-6 mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header py-2">
                            <h6 class="card-title mb-0 text-capitalize">{{ str_replace('_', ' ', $module) }}</h6>
                        </div>
                        <div class="card-body py-2">
                            @foreach($items as $item)
                            @php $rm = $riskLevels[$item['risk']] ?? ['label'=>$item['risk'],'color'=>'secondary']; @endphp
                            <div class="form-check mb-2 d-flex align-items-start gap-2">
                                @if($item['inherited'])
                                    <input class="form-check-input mt-1" type="checkbox" disabled checked
                                           title="Inherited from role — manage on the role page.">
                                @else
                                    <input class="form-check-input mt-1" type="checkbox"
                                           name="permissions[]" value="{{ $item['name'] }}"
                                           id="upm-{{ $item['id'] }}"
                                           {{ $item['direct'] ? 'checked' : '' }}>
                                @endif
                                <label class="form-check-label flex-grow-1"
                                       for="upm-{{ $item['id'] }}"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       title="{{ $item['description'] }} ({{ $item['name'] }})">
                                    {{ ucfirst(str_replace($module . '.', '', $item['name'])) }}
                                    <span class="badge bg-{{ $rm['color'] }} fs-10 ms-1">{{ $rm['label'] }}</span>
                                    @if($item['inherited'])
                                        <span class="badge bg-info-transparent text-info fs-10 ms-1">via role</span>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3 mb-3">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i>Save Direct Permissions
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
</script>
@endpush
