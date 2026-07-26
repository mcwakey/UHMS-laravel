{{--
    Phase 14R.5.1 — shared behaviour for handoff modals.

    Two responsibilities only; there is NO business logic here:

      1. Lazy patient-scoped Pregnancy Profile search. Candidates are fetched
         from the server the first time a selector is opened — never on page
         render. The endpoint derives the patient from its own route-bound
         record, so nothing sent from the browser can widen that scope.

      2. Re-open the modal that failed validation, using the project's existing
         flash convention (same approach as the Blood Bank create-request
         modal), so the clinician does not lose their place.

    Uses the select2 already bundled by the application. No second modal
    framework, no new frontend framework, no duplicate-detection logic — the
    server remains authoritative for every decision.

    Expects (optional): $reopenModalId
--}}
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.select2) {
        return;
    }

    document.querySelectorAll('select.maternity-profile-select').forEach(function (el) {
        var url = el.dataset.searchUrl;
        if (!url) {
            return;
        }

        var modalSelector = el.dataset.modal;
        var modalEl = modalSelector ? document.querySelector(modalSelector) : null;

        jQuery(el).select2({
            dropdownParent: modalEl ? jQuery(modalEl) : undefined,
            width: '100%',
            allowClear: true,
            placeholder: el.dataset.placeholder || '',
            // Opening the control is what triggers the first request; the list
            // is never present in the rendered page.
            ajax: {
                url: url,
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    return { results: (data || []).map(function (row) {
                        return { id: String(row.id), text: row.text };
                    }) };
                },
                cache: true
            }
        });
    });
});
</script>
@endpush
@endonce

@if (! empty($reopenModalId))
@push('scripts')
<script>
// Validation failed for this handoff: re-open its dialog so the messages and
// preserved input are visible where they were entered.
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById(@json($reopenModalId));
    if (el && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
});
</script>
@endpush
@endif
