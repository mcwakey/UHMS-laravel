<tr>
    <td>
        <select name="lines[{{ $index }}][account_id]" class="form-select form-select-sm" required>
            <option value="">{{ __('accounting.select_account') }}</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) ($line['account_id'] ?? '') === (string) $account->id)>{{ $account->display_name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $line['description'] ?? '' }}">
    </td>
    <td>
        <select name="lines[{{ $index }}][department_id]" class="form-select form-select-sm">
            <option value="">{{ __('accounting.none') }}</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) ($line['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="lines[{{ $index }}][debit]" class="form-control form-control-sm text-end line-debit" value="{{ $line['debit'] ?? '' }}">
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="lines[{{ $index }}][credit]" class="form-control form-control-sm text-end line-credit" value="{{ $line['credit'] ?? '' }}">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-line aria-label="{{ __('accounting.remove_line') }}" title="{{ __('accounting.remove_line') }}"><i class="ti ti-trash"></i></button>
    </td>
</tr>
