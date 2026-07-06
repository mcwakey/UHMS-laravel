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
@php
    $billingContext = $specialtyBillingContext ?? [];
    $defaultBillingService = data_get($billingContext, 'default_service.service');
    $billingMapping = data_get($billingContext, 'default_service');
    $billingWarnings = collect($billingContext['warnings'] ?? []);
@endphp
@if($defaultBillingService || ($billingWarnings->isNotEmpty() && auth()->user()?->can('invoices.create')))
    <div class="card mb-3 border-start border-3 border-info">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-semibold small text-uppercase text-muted">{{ __('consultation_specialties.billing.title') }}</div>
                    @if($defaultBillingService)
                        <div class="fw-semibold">{{ $defaultBillingService['name'] }}</div>
                        <small class="text-muted">{{ $defaultBillingService['code'] ?? '' }} · {{ __('consultation_specialties.billing.mapped_service') }}</small>
                    @else
                        <div class="fw-semibold text-warning">{{ __('consultation_specialties.billing.no_mapped_service') }}</div>
                        <small class="text-muted">{{ __('consultation_specialties.billing.visible_to_authorized_users') }}</small>
                    @endif
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    @if($defaultBillingService)
                        <span class="badge bg-{{ data_get($billingContext, 'already_billed') ? 'success' : 'warning text-dark' }}">
                            {{ data_get($billingContext, 'already_billed') ? __('consultation_specialties.billing.already_billed') : __('consultation_specialties.billing.not_billed') }}
                        </span>
                        @if(! empty($billingContext['billable_suggestions'] ?? []))
                            <span class="badge bg-soft-info">{{ __('consultation_specialties.billing.billable_suggestions') }}: {{ count($billingContext['billable_suggestions']) }}</span>
                        @endif
                        @if(! empty($billingContext['preview_url']))
                            <a href="{{ $billingContext['preview_url'] }}" class="btn btn-outline-info btn-xs">{{ __('consultation_specialties.billing.preview_billing') }}</a>
                        @endif
                        @can('invoices.create')
                            @if(empty($billingContext['already_billed']) && ! empty($billingContext['apply_url']))
                                <form method="POST" action="{{ $billingContext['apply_url'] }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="confirmed" value="1">
                                    <button class="btn btn-outline-primary btn-xs" data-confirm="{{ __('consultation_specialties.billing.confirm_apply_charge') }}">{{ __('consultation_specialties.billing.apply_charge') }}</button>
                                </form>
                            @endif
                        @endcan
                    @endif
                </div>
            </div>
            @if($billingWarnings->isNotEmpty() && auth()->user()?->can('invoices.create'))
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach($billingWarnings as $warning)
                        <span class="badge bg-warning text-dark">{{ $warning }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
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
