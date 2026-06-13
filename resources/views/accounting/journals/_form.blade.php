@php
    $isEdit = isset($journal);
    $oldLines = old('lines');
    if (! $oldLines) {
        $oldLines = $isEdit
            ? $journal->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'department_id' => $line->department_id,
            ])->toArray()
            : [
                ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => '', 'department_id' => ''],
                ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => '', 'department_id' => ''],
            ];
    }
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.journal_date') }} <span class="text-danger">*</span></label>
        <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', isset($journal) ? $journal->entry_date->toDateString() : now()->toDateString()) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('accounting.reference_number') }}</label>
        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $journal->reference_number ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('accounting.description') }} <span class="text-danger">*</span></label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $journal->description ?? '') }}" required>
    </div>
</div>

<div class="card border mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('accounting.journal_lines') }}</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addJournalLine"><i class="ti ti-plus me-1"></i>{{ __('accounting.add_line') }}</button>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="journalLinesTable">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 260px;">{{ __('accounting.account') }}</th>
                    <th style="min-width: 220px;">{{ __('accounting.line_description') }}</th>
                    <th style="min-width: 150px;">{{ __('accounting.department') }}</th>
                    <th class="text-end" style="width: 140px;">{{ __('accounting.debit') }}</th>
                    <th class="text-end" style="width: 140px;">{{ __('accounting.credit') }}</th>
                    <th style="width: 48px;"></th>
                </tr>
            </thead>
            <tbody id="journalLinesBody">
                @foreach($oldLines as $index => $line)
                    @include('accounting.journals._line', ['index' => $index, 'line' => $line])
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">{{ __('accounting.totals') }}</th>
                    <th class="text-end" id="totalDebit">0.00</th>
                    <th class="text-end" id="totalCredit">0.00</th>
                    <th></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">{{ __('accounting.difference') }}</th>
                    <th colspan="2" class="text-end" id="journalDifference">0.00</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<template id="journalLineTemplate">
    @include('accounting.journals._line', ['index' => '__INDEX__', 'line' => ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => '', 'department_id' => '']])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var body = document.getElementById('journalLinesBody');
    var addButton = document.getElementById('addJournalLine');
    var template = document.getElementById('journalLineTemplate');
    var nextIndex = body ? body.querySelectorAll('tr').length : 0;

    function money(value) {
        var number = parseFloat(value || '0');
        return isNaN(number) ? 0 : number;
    }

    function recalc() {
        var debit = 0;
        var credit = 0;
        body.querySelectorAll('.line-debit').forEach(function (input) { debit += money(input.value); });
        body.querySelectorAll('.line-credit').forEach(function (input) { credit += money(input.value); });
        document.getElementById('totalDebit').textContent = debit.toFixed(2);
        document.getElementById('totalCredit').textContent = credit.toFixed(2);
        var diff = debit - credit;
        var diffEl = document.getElementById('journalDifference');
        diffEl.textContent = diff.toFixed(2);
        diffEl.classList.toggle('text-success', Math.abs(diff) < 0.005);
        diffEl.classList.toggle('text-danger', Math.abs(diff) >= 0.005);
    }

    addButton && addButton.addEventListener('click', function () {
        var html = template.innerHTML.replaceAll('__INDEX__', nextIndex++);
        body.insertAdjacentHTML('beforeend', html);
        recalc();
    });

    body && body.addEventListener('input', recalc);
    body && body.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-line]');
        if (!button) return;
        if (body.querySelectorAll('tr').length <= 2) return;
        button.closest('tr').remove();
        recalc();
    });

    recalc();
});
</script>
@endpush
