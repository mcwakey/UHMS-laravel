@can('maternity.billing.preview')
@if(! empty($billingPreviews) && $billingPreviews->isNotEmpty())
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('maternity.billing_preview') }}</h5>
        <span class="badge bg-secondary">{{ __('maternity.billing_posting_disabled') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('maternity.mapping_key') }}</th>
                        <th>{{ __('maternity.service') }}</th>
                        <th class="text-end">{{ __('billing.amount') }}</th>
                        <th>{{ __('maternity.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($billingPreviews as $preview)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $preview['label'] }}</div>
                            <div class="small text-muted">{{ $preview['invoice_source_type'] }}</div>
                        </td>
                        <td>
                            @if($preview['service'])
                            <span class="fw-semibold">{{ $preview['service']->name }}</span>
                            <div class="small text-muted">{{ $preview['service']->code }}</div>
                            @else
                            <span class="text-muted">{{ __('maternity.mapping_missing') }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $preview['amount'] !== null ? number_format($preview['amount'], 2) : __('common.none') }}</td>
                        <td>
                            <span class="badge bg-{{ in_array($preview['status'], ['missing_mapping', 'mapping_disabled', 'service_inactive', 'newborn_billing_disabled'], true) ? 'warning' : ($preview['status'] === 'already_posted' ? 'success' : 'secondary') }}">
                                {{ $preview['status_label'] }}
                            </span>
                            <div class="small text-muted">{{ $preview['reason'] }}</div>
                            @foreach($preview['warnings'] as $warning)
                            <div class="small text-warning">{{ $warning }}</div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer small text-muted">
        {{ __('maternity.billing_preview_no_posting') }}
    </div>
</div>
@endif
@endcan
