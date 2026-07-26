{{--
    Phase 14R.6.1 — "View Current Maternity Record" as an EXPLICIT, separate,
    lazily loaded action on a completed consultation.

    The completed page keeps its bounded snapshot query count: the live record
    is fetched only when the clinician asks for it, through a GET endpoint that
    re-checks the bridge permission and the underlying maternity permission.

    It is visually and textually separated from the historical snapshot, and it
    never compares, overwrites or rewrites it.

    Expects: $vm
--}}
@php($vm = $vm ?? null)

@if ($vm && $vm->currentRecordAvailable && $vm->mayViewCurrentRecord && $vm->route('current'))
    <div class="mt-3 border-top pt-3" id="maternity-current-record-block">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <div class="fw-semibold small">{{ __('consultation_maternity_summary.summary.current_record') }}</div>
                <div class="text-muted" style="font-size: .74rem;">
                    {{ __('consultation_maternity_summary.current.not_part_of_snapshot') }}
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary"
                    id="loadCurrentMaternityRecordBtn"
                    data-url="{{ $vm->route('current') }}"
                    data-target="#maternity-current-record-body">
                {{ __('consultation_maternity_summary.current.view') }}
            </button>
        </div>

        <div class="text-muted mt-1" style="font-size: .74rem;">
            {{ __('consultation_maternity_summary.current.may_differ') }}
        </div>

        {{-- Filled on demand; empty on first paint, so the completed page pays
             no live-maternity query cost. --}}
        <div id="maternity-current-record-body" class="mt-2"></div>
    </div>

    @once
        @push('scripts')
        <script>
        // Lazy fetch only. No business logic, no comparison, no write.
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('loadCurrentMaternityRecordBtn');
            if (!btn) { return; }

            btn.addEventListener('click', function () {
                var body = document.querySelector(btn.dataset.target);
                if (!body) { return; }

                if (body.dataset.loaded === '1') {
                    body.classList.toggle('d-none');
                    return;
                }

                btn.disabled = true;
                fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        body.innerHTML = html;
                        body.dataset.loaded = '1';
                    })
                    .finally(function () { btn.disabled = false; });
            });
        });
        </script>
        @endpush
    @endonce
@endif
