@if(! empty($specialtyOrderSets ?? []))
<div class="card mb-3" id="order-sets-panel">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="fw-bold mb-0"><i class="ti ti-packages me-1"></i>{{ __('consultation_specialties.order_sets.title') }}</h6>
        <span class="badge bg-light text-dark border">{{ count($specialtyOrderSets) }} {{ __('consultation_specialties.order_sets.items') }}</span>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach($specialtyOrderSets as $orderSet)
                <div class="border rounded p-2 flex-grow-1" style="min-width:220px;max-width:320px;">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold small">{{ $orderSet['name'] }}</div>
                            @if($orderSet['description'])
                                <small class="text-muted d-block">{{ $orderSet['description'] }}</small>
                            @endif
                        </div>
                        <span class="badge bg-{{ $orderSet['color'] ?? 'secondary' }}-subtle text-{{ $orderSet['color'] ?? 'secondary' }}">{{ $orderSet['items_count'] }}</span>
                    </div>
                    @can('consultations.create')
                        <button type="button"
                                class="btn btn-outline-primary btn-sm mt-2"
                                data-order-set-preview
                                data-preview-url="{{ route('admin.consultations.specialty-order-sets.preview', [$visit, $orderSet['id']]) }}"
                                data-apply-url="{{ route('admin.consultations.specialty-order-sets.apply', [$visit, $orderSet['id']]) }}">
                            <i class="ti ti-eye me-1"></i>{{ __('consultation_specialties.order_sets.preview') }}
                        </button>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="modal fade" id="specialtyOrderSetModal" tabindex="-1" aria-labelledby="specialtyOrderSetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="specialtyOrderSetModalLabel">{{ __('consultation_specialties.order_sets.preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" data-order-set-intro>{{ __('consultation_specialties.order_sets.preview_intro') }}</div>
                <div data-order-set-errors class="alert alert-danger d-none"></div>
                <div data-order-set-items></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" data-order-set-apply>
                    <i class="ti ti-check me-1"></i>{{ __('consultation_specialties.order_sets.apply_selected') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('order-sets-panel');
    const modalEl = document.getElementById('specialtyOrderSetModal');
    if (!panel || !modalEl) return;

    const itemsEl = modalEl.querySelector('[data-order-set-items]');
    const errorsEl = modalEl.querySelector('[data-order-set-errors]');
    const applyBtn = modalEl.querySelector('[data-order-set-apply]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let applyUrl = null;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const routeInput = () => document.querySelector('input[name="consultation_route_id"]')?.value || '{{ $selectedRoute?->id }}';

    function showError(message) {
        errorsEl.textContent = message;
        errorsEl.classList.remove('d-none');
    }

    function renderItems(items) {
        itemsEl.innerHTML = items.map((item) => {
            const badge = item.can_apply ? '{{ __('consultation_specialties.order_sets.can_apply') }}' : '{{ __('consultation_specialties.order_sets.manual_action') }}';
            const checked = item.can_apply ? 'checked' : '';
            const disabled = item.can_apply ? '' : 'disabled';
            const warnings = (item.warnings || []).map((warning) => `<div class="small text-warning">${esc(warning)}</div>`).join('');
            return `<label class="border rounded p-2 mb-2 d-flex gap-2 align-items-start">
                <input type="checkbox" class="form-check-input mt-1" value="${esc(item.id)}" ${checked} ${disabled}>
                <span class="flex-grow-1">
                    <span class="fw-semibold">${esc(item.label)}</span>
                    <span class="badge bg-light text-dark border ms-1">${esc(item.type)}</span>
                    <span class="badge ${item.can_apply ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'} ms-1">${badge}</span>
                    ${warnings}
                </span>
            </label>`;
        }).join('') || '<div class="text-muted">{{ __('consultation_specialties.order_sets.no_order_sets') }}</div>';
    }

    panel.addEventListener('click', function (event) {
        const button = event.target.closest('[data-order-set-preview]');
        if (!button) return;

        applyUrl = button.dataset.applyUrl;
        errorsEl.classList.add('d-none');
        itemsEl.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();

        const url = new URL(button.dataset.previewUrl, window.location.origin);
        url.searchParams.set('consultation_route_id', routeInput());
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((response) => response.ok ? response.json() : response.json().then((json) => Promise.reject(json)))
            .then((json) => {
                modalEl.querySelector('#specialtyOrderSetModalLabel').textContent = json.preview.order_set.name;
                renderItems(json.preview.items || []);
            })
            .catch((error) => showError(error.message || '{{ __('consultation_specialties.order_sets.failed') }}'));
    });

    applyBtn.addEventListener('click', function () {
        if (!applyUrl) return;
        const ids = Array.from(itemsEl.querySelectorAll('input[type="checkbox"]:checked')).map((input) => input.value);
        if (!window.confirm('{{ __('consultation_specialties.order_sets.confirm_apply') }}')) return;

        applyBtn.disabled = true;
        fetch(applyUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ consultation_route_id: routeInput(), selected_item_ids: ids }),
        })
            .then((response) => response.ok ? response.json() : response.json().then((json) => Promise.reject(json)))
            .then(() => window.location.reload())
            .catch((error) => showError(error.message || '{{ __('consultation_specialties.order_sets.failed') }}'))
            .finally(() => { applyBtn.disabled = false; });
    });
});
</script>
@endpush
@endif
