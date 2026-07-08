@php
    $doctorWorkspace = isset($doctorSpecialtyWorkspace) && $doctorSpecialtyWorkspace ? $doctorSpecialtyWorkspace->toArray() : [];
    $workspaceActions = collect($doctorWorkspace['quick_actions'] ?? []);
    $pinnedKeys = collect($doctorWorkspace['pinned_actions'] ?? []);
    $compactWorkspace = (bool) data_get($doctorWorkspace, 'preferences.compact_mode', false);
    $visibleMetrics = collect($doctorWorkspace['metrics'] ?? [])->take($compactWorkspace ? 3 : 6);
    $visibleActions = $pinnedKeys->isNotEmpty()
        ? $workspaceActions->filter(fn ($action) => $pinnedKeys->contains($action['key']))->concat($workspaceActions->reject(fn ($action) => $pinnedKeys->contains($action['key'])))->take($compactWorkspace ? 4 : 9)
        : $workspaceActions->take($compactWorkspace ? 4 : 9);
@endphp

<div class="card mb-3 specialty-workspace-strip" style="--specialty-accent: {{ $specialtyAccent }}" id="doctor-specialty-workspace">
    <div class="card-body py-2">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm bg-light text-primary border">
                    <i class="ti {{ $specialtyLayout['profile']['icon'] ?? 'ti-stethoscope' }}"></i>
                </span>
                <div>
                    <div class="fw-semibold">{{ data_get($doctorWorkspace, 'doctor.display_name', 'Dr. '.(auth()->user()?->full_name ?? '')) }}</div>
                    <small class="text-muted">
                        {{ data_get($doctorWorkspace, 'specialty.workspace_name', __('consultation_specialties.workspace.title', ['profile' => $specialtyLayout['profile']['translated_name'] ?? __('consultation_specialties.profiles.general_medicine')])) }}
                        @if(data_get($doctorWorkspace, 'department.name'))
                            &middot; {{ __('consultation_specialties.workspace.department') }}: {{ data_get($doctorWorkspace, 'department.name') }}
                        @endif
                        &middot; {{ $selectedRouteLabel }}
                    </small>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.consultations.preferences.layout.update') }}" class="d-flex align-items-center gap-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="preferred_layout" value="{{ $compactWorkspace ? 'default' : 'compact' }}">
                <input type="hidden" name="compact_mode" value="{{ $compactWorkspace ? 0 : 1 }}">
                <button type="submit" class="btn btn-outline-secondary btn-xs">
                    <i class="ti ti-layout-sidebar-left-collapse me-1"></i>{{ __('consultation_specialties.workspace.compact_mode') }}
                </button>
            </form>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
            @forelse($visibleMetrics as $metric)
                <span class="badge bg-light text-dark border">{{ $metric['label'] }}: {{ $metric['value'] }}</span>
            @empty
                <span class="small text-muted">{{ __('consultation_specialties.workspace.no_metrics') }}</span>
            @endforelse
        </div>

        @if(! empty($doctorWorkspace['alerts'] ?? []))
            <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($doctorWorkspace['alerts'] as $alert)
                    <a href="{{ $alert['target'] ?? '#' }}" class="badge bg-{{ ($alert['severity'] ?? 'info') === 'warning' ? 'warning text-dark' : 'info-subtle text-info' }} text-decoration-none">
                        {{ $alert['message'] }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="small text-muted align-self-center">{{ __('consultation_specialties.workspace.quick_actions') }}:</span>
            @foreach($visibleActions as $action)
                <a href="{{ $action['target'] ?? '#' }}"
                   class="btn btn-outline-primary btn-xs"
                   data-doctor-workspace-action="{{ $action['type'] }}"
                   data-target="{{ $action['target'] ?? '' }}">
                    <i class="ti {{ $action['icon'] ?? 'ti-circle' }} me-1"></i>{{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
{{-- Phase 16.5: billing/service mapping is admin/finance-facing. The doctor
     consultation workspace intentionally renders no billing card; mappings
     remain available in admin configuration, reports, and finance flows. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-doctor-workspace-action]').forEach(function (action) {
        action.addEventListener('click', function (event) {
            const type = action.dataset.doctorWorkspaceAction;
            const targetSelector = action.dataset.target || action.getAttribute('href');
            if (type === 'generate_summary') {
                event.preventDefault();
                document.querySelector(targetSelector)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.getElementById('generateSpecialtySummaryBtn')?.click();
                return;
            }
            if (targetSelector && targetSelector.startsWith('#')) {
                const target = document.querySelector(targetSelector);
                if (target) {
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (target.classList.contains('tab-pane')) {
                        const trigger = document.querySelector(`[data-bs-target="${targetSelector}"], [href="${targetSelector}"]`);
                        trigger?.click();
                    }
                }
            }
        });
    });
});
</script>
