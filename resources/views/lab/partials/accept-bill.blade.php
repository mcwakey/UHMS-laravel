{{-- ===================== SELECTIVE ACCEPTANCE & BILLING =====================
     Shown for every investigation department (lab, radiology, scan, …), not just
     parameter/lab-test requests. Lists this request's pending items so they can
     be accepted and invoiced. --}}
@if(($showBillingAcceptance ?? true) && $pendingItems->count() > 0)
<div class="card mb-3">
    <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-list-check me-1"></i>{{ __('lab.select_items_to_accept') }}</h6>
        <small class="text-muted"><span id="selCount">0</span> of {{ $pendingItems->count() }} selected</small>
    </div>
    <div class="card-body">
        <form id="acceptSelectedForm" method="POST" action="{{ route('admin.lab.requests.accept-selected', $request) }}">
            @csrf
            <div id="acceptSelectedErrors" class="alert alert-danger d-none small py-2 mb-2"></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="selectAllItems" class="form-check-input"></th>
                            <th>{{ __('lab.item_col') }}</th>
                            <th>{{ __('lab.status_col') }}</th>
                            <th class="text-end">{{ __('lab.price_col') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($request->items as $it)
                        @if($it->status === 'pending')
                        @php
                            $pricing = ($billingPrices ?? [])[$it->id] ?? null;
                            $price = $pricing['selected_price'] ?? null;
                        @endphp
                        <tr data-price="{{ (float) ($price ?? 0) }}">
                            <td>
                                <input type="checkbox" name="item_ids[]" value="{{ $it->id }}" class="form-check-input acceptSelectedCb">
                            </td>
                            <td>
                                <span class="fw-medium">{{ $it->display_name }}</span>
                                @if($it->service)<small class="text-muted d-block">{{ $it->service->code ?? '' }}</small>@endif
                            </td>
                            <td><span class="badge bg-{{ $it->status_color }}">{{ ucfirst($it->status) }}</span></td>
                            <td class="text-end">
                                {{ $price !== null ? number_format($price, 2) : '—' }}
                                @if(($pricing['payer_type'] ?? null) === 'insurance')
                                    <small class="d-block text-muted">
                                        {{ ($pricing['pricing_source'] ?? null) === 'fallback_cash_no_insurance_price' ? 'Cash fallback' : 'Insurance tariff' }}
                                    </small>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">{{ __('lab.selected_total') }}</td>
                            <td class="text-end fw-bold" id="acceptSelectedTotal">GH₵ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ __('lab.accept_billing_note') }}</small>
                <button type="submit" id="acceptSelectedBtn" class="btn btn-success" disabled>
                    <i class="ti ti-check me-1"></i>{{ __('lab.bill_selected') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@push('scripts')
<script>
(function() {
    const form = document.getElementById('acceptSelectedForm');
    if (!form) return;
    const cbs   = form.querySelectorAll('.acceptSelectedCb');
    const all   = document.getElementById('selectAllItems');
    const cnt   = document.getElementById('selCount');
    const btn   = document.getElementById('acceptSelectedBtn');
    const errs  = document.getElementById('acceptSelectedErrors');
    const totalEl = document.getElementById('acceptSelectedTotal');
    const sync = () => {
        const selected = [...cbs].filter(c => c.checked);
        cnt.textContent = selected.length;
        btn.disabled = selected.length === 0;
        all.checked = selected.length === cbs.length && cbs.length > 0;
        all.indeterminate = selected.length > 0 && selected.length < cbs.length;
        if (totalEl) {
            const total = selected.reduce((sum, c) => sum + (parseFloat(c.closest('tr')?.dataset.price) || 0), 0);
            totalEl.textContent = 'GH₵ ' + total.toFixed(2);
        }
    };
    all.addEventListener('change', () => { cbs.forEach(c => c.checked = all.checked); sync(); });
    cbs.forEach(c => c.addEventListener('change', sync));
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errs.classList.add('d-none');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';
        try {
            const fd = new FormData(form);
            const r = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                errs.textContent = data.error || ('Error ' + r.status);
                errs.classList.remove('d-none');
                btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i>Accept Selected &amp; Generate Invoice';
                return;
            }
            const target = data.redirect || window.location.href;
            if (window.UhmsInertia && data.redirect) {
                window.UhmsInertia.visit(target, { preserveScroll: true });
            } else {
                window.location.href = target;
            }
        } catch (err) {
            errs.textContent = err.message;
            errs.classList.remove('d-none');
            btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i>Accept Selected &amp; Generate Invoice';
        }
    });
})();
</script>
@endpush
