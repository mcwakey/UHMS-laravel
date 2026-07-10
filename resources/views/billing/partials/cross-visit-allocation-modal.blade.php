{{--
    Shared cross-visit payment allocation modal. Include once per page; trigger
    buttons populate it via data-* attributes:
      data-action     → POST url (admin.billing.previous-balance.allocate)
      data-patient    → patient display name
      data-total      → total patient outstanding (default amount)
      data-previous   → previous-visit outstanding
      data-visit-id   → current visit id (settled last in oldest-first)
    Requires billing.payment.allocate_cross_visit.
--}}
@can('billing.payment.allocate_cross_visit')
<div class="modal fade" id="crossVisitAllocationModal" tabindex="-1" aria-labelledby="crossVisitAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="crossVisitAllocationForm" action="">
            @csrf
            <input type="hidden" name="visit_id" id="cva_visit_id" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crossVisitAllocationModalLabel">
                        <i class="ti ti-cash me-1"></i>{{ __('billing.split_payment_across_visits') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 small mb-3" id="cva_summary">
                        <span id="cva_patient" class="fw-semibold"></span> —
                        {{ __('billing.previous_visits_outstanding') }}: <span id="cva_previous" class="fw-bold"></span> ·
                        {{ __('billing.total_patient_outstanding') }}: <span id="cva_total" class="fw-bold"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">{{ __('billing.allocation_mode') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="cva_mode_oldest" value="oldest_first" checked>
                            <label class="form-check-label" for="cva_mode_oldest">{{ __('billing.oldest_outstanding_first') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="cva_mode_current" value="current_visit">
                            <label class="form-check-label" for="cva_mode_current">{{ __('billing.pay_current_visit_only') }}</label>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-5">
                            <label for="cva_amount" class="form-label small">{{ __('payments.amount_lbl') }} <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="cva_amount" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-sm-7">
                            <label for="cva_method" class="form-label small">{{ __('payments.method_lbl') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" id="cva_method" class="form-select" required>
                                <option value="cash">{{ __('payments.cash') }}</option>
                                <option value="bank_transfer">{{ __('payments.bank_transfer') }}</option>
                                <option value="card">{{ __('payments.card') }}</option>
                                <option value="cheque">{{ __('payments.cheque') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="cva_reference" class="form-label small">{{ __('payments.reference_lbl') }}</label>
                            <input type="text" name="reference_number" id="cva_reference" class="form-control" placeholder="{{ __('common.optional') }}">
                        </div>
                    </div>
                    <p class="form-text mt-2 mb-0">{{ __('billing.oldest_outstanding_first') }} — {{ __('billing.previous_balance_warning', ['amount' => '']) }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('billing.collect_old_balance') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-cross-visit-allocate]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = document.getElementById('crossVisitAllocationForm');
        form.setAttribute('action', btn.dataset.action || '');
        document.getElementById('cva_visit_id').value = btn.dataset.visitId || '';
        document.getElementById('cva_patient').textContent = btn.dataset.patient || '';
        document.getElementById('cva_previous').textContent = btn.dataset.previousLabel || '';
        document.getElementById('cva_total').textContent = btn.dataset.totalLabel || '';
        var amount = document.getElementById('cva_amount');
        amount.value = btn.dataset.total || '';
        amount.setAttribute('max', btn.dataset.total || '');
    });
});
</script>
@endpush
@endcan
