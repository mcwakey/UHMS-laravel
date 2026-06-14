@extends('layouts.app')
@section('title', __('admin.broadcast_notification'))

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Broadcast Notification</h4>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.notifications.broadcast.store') }}" class="card p-3">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Target type</label>
            <select name="target_type" id="targetType" class="form-select" required>
                <option value="role">{{ __('admin.role') }}</option>
                <option value="permission">{{ __('admin.permission') }}</option>
                <option value="department">{{ __('common.department') }}</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Target value</label>
            <select name="target_value" id="targetValue" class="form-select" required>
                @foreach($roles as $r)
                    <option data-type="role" value="{{ $r }}">{{ $r }}</option>
                @endforeach
                @foreach($permissions as $p)
                    <option data-type="permission" value="{{ $p }}" hidden>{{ $p }}</option>
                @endforeach
                @foreach($departments as $d)
                    <option data-type="department" value="{{ $d->id }}" hidden>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Module</label>
            <select name="module" class="form-select">
                @foreach($modules as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-select">
                @foreach($priorities as $p)
                    <option value="{{ $p->value }}" {{ $p->value === 'NORMAL' ? 'selected' : '' }}>{{ $p->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Optional URL</label>
            <input type="text" name="url" class="form-control" placeholder="/admin/...">
        </div>
        <div class="col-md-12">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="191" required>
        </div>
        <div class="col-md-12">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="3" maxlength="1000" required></textarea>
        </div>
    </div>
    <div class="text-end mt-3">
        <button class="btn btn-primary">Send broadcast</button>
    </div>
</form>

<script>
document.getElementById('targetType').addEventListener('change', function () {
    const t = this.value;
    document.querySelectorAll('#targetValue option').forEach(o => {
        const match = o.dataset.type === t;
        o.hidden = !match;
        if (match && !document.querySelector('#targetValue option:not([hidden]):checked')) {
            o.selected = true;
        }
    });
});
</script>
@endsection
