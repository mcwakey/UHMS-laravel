{{--
    Phase 14R.5 — shared READ-ONLY maternity summary cards.

    Consumed by the Emergency workspace, the Admission workspace and the
    Consultation maternity panels. Renders ONLY what MaternityContextCardBuilder
    already prepared: this partial issues ZERO queries and contains no editing
    form. Every card states its owner module and its source record.

    Expects: $cards (list of prepared card arrays)
--}}
@php($cards = $cards ?? [])

@if (! empty($cards))
    <div class="row g-2 maternity-context-cards">
        @foreach ($cards as $card)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="border rounded p-2 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <div class="fw-semibold small">{{ $card['title'] }}</div>
                        <span class="badge bg-light text-secondary border fw-normal"
                              title="{{ __('maternity_handoffs.ownership.operational_owner') }}">
                            {{ $card['owner_label'] }}
                        </span>
                    </div>

                    <div class="text-muted" style="font-size: .75rem;">{{ $card['record'] }}</div>

                    @if (! empty($card['rows']))
                        <dl class="row mb-0 mt-2 g-0" style="font-size: .8rem;">
                            @foreach ($card['rows'] as $row)
                                <dt class="col-6 fw-normal text-muted text-truncate">{{ $row['label'] }}</dt>
                                <dd class="col-6 mb-1 text-end">{{ $row['value'] }}</dd>
                            @endforeach
                        </dl>
                    @endif

                    @if (! empty($card['note']))
                        <div class="text-muted fst-italic mt-1" style="font-size: .72rem;">{{ $card['note'] }}</div>
                    @endif

                    @if (! empty($card['url']))
                        <a href="{{ $card['url'] }}" class="btn btn-sm btn-link px-0 mt-1">
                            {{ __('maternity_handoffs.cards.open_record') }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
