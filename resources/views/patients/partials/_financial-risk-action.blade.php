@php
    $modalId = 'frAction_'.\Illuminate\Support\Str::slug($action);
    $routeName = 'admin.patients.financial-risk.'.$action;
@endphp

<button type="button" class="btn btn-sm {{ $btn }}" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">{{ $label }}</button>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ $workspaceRoutes->route($routeName, [$patient, $financialRisk]) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $label }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('patient_financial_risk.actions.cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="{{ $modalId }}_reason">{{ __('patient_financial_risk.history.reason') }}</label>
                        <textarea name="reason" id="{{ $modalId }}_reason" rows="3" class="form-control" maxlength="1000" @if($reasonRequired) required @endif></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('patient_financial_risk.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('patient_financial_risk.actions.confirm') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
