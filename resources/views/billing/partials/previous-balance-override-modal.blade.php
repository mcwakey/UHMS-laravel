{{-- Previous-balance OPD override. Include on a page that has a $visit in scope. --}}
@can('billing.previous_balance.override')
<div class="modal fade" id="previousBalanceOverrideModal" tabindex="-1" aria-labelledby="previousBalanceOverrideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.billing.previous-balance.override', $visit) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previousBalanceOverrideModalLabel">
                        <i class="ti ti-key me-1"></i>{{ __('billing.previous_balance_override_required') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ __('billing.previous_balance_warning', ['amount' => '']) }}
                    </p>
                    <div class="mb-2">
                        <label for="previousBalanceOverrideReason" class="form-label">{{ __('billing.override_reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" id="previousBalanceOverrideReason" class="form-control" rows="3" minlength="5" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-shield-check me-1"></i>{{ __('billing.approve_override') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
