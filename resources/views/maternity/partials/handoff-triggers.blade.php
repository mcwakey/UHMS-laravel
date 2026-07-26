{{--
    Phase 14R.5.1 — renders every handoff trigger from typed action models.

    A trigger is a clickable button ONLY when the action resolved to `enabled`
    or `existing_record`. Every other state renders its honest reason instead,
    so the UI never offers something the server will reject.

    Zero queries and zero decisions: state, label, modal id, route and reuse all
    arrive already resolved.

    Expects: $actions (iterable of MaternityHandoffActionViewModel)
--}}
@php($actions = $actions ?? [])

@if (! empty($actions))
    <div class="d-flex flex-wrap gap-2 mt-3 maternity-handoff-triggers">
        @foreach ($actions as $action)
            @continue(! $action->visible)

            @if ($action->isExecutable())
                <button type="button"
                        id="{{ $action->modalId }}Trigger"
                        class="btn btn-sm {{ $action->reusesExistingRecord() ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#{{ $action->modalId }}"
                        data-handoff-state="{{ $action->state }}">
                    {{ $action->label }}
                </button>
            @elseif ($action->showsReason())
                {{-- Not clickable, and honest about why. --}}
                <span class="badge bg-light text-secondary border fw-normal py-2 px-2"
                      data-handoff-state="{{ $action->state }}"
                      data-handoff-action="{{ $action->actionKey }}">
                    {{ $action->label }} — {{ $action->disabledReason }}
                    @if (filled($action->fallbackUrl))
                        {{-- K1 fallback: a real link into the existing standard
                             consultation-creation flow, not a dead end. --}}
                        <a href="{{ $action->fallbackUrl }}" class="ms-1">
                            {{ $action->fallbackLabel }}
                        </a>
                    @endif
                </span>
            @endif
        @endforeach
    </div>
@endif
