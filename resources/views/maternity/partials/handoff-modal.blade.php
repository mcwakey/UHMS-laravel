{{--
    Phase 14R.5.1 — shared handoff modal shell.

    Renders ONE MaternityHandoffActionViewModel. Blade makes no decisions here:
    permissions, lifecycle, context, reuse and the return URL all arrive already
    resolved. This partial performs ZERO queries.

    Uses the project's existing Bootstrap 5 modal convention (same markup as
    addNursingNoteModal et al): modal > modal-dialog > form.modal-content.

    Expects: $action (MaternityHandoffActionViewModel)
--}}
@php($action = $action ?? null)

@if ($action && $action->isExecutable())
    @php($modalId = $action->modalId)
    @php($fieldsPartial = 'maternity.partials.handoff-fields.'.str_replace('_', '-', $action->actionKey))

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
         aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="{{ $action->url() }}" class="modal-content"
                  data-handoff-action="{{ $action->actionKey }}">
                @csrf
                @if (! in_array($action->method, ['GET', 'POST'], true))
                    @method($action->method)
                @endif

                {{-- Safe return context: named internal route + scalar params
                     only. An external URL is structurally inexpressible. --}}
                @foreach ($action->returnFields() as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                {{-- Lets the page re-open THIS dialog after a validation
                     redirect, using the framework's own old-input mechanism —
                     no session key and no controller change required. --}}
                <input type="hidden" name="_handoff_modal" value="{{ $modalId }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $action->label }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('common.close') }}"></button>
                </div>

                <div class="modal-body">
                    @if (filled($action->description))
                        <p class="text-muted small">{{ $action->description }}</p>
                    @endif

                    @if ($action->reusesExistingRecord())
                        {{-- Idempotency made visible: this opens the existing
                             record; submitting again never creates a second. --}}
                        <div class="alert alert-info py-2 px-3 small">
                            <div class="fw-semibold">{{ __('maternity_handoffs.states.existing_record_reused') }}</div>
                            <div>
                                {{ $action->existingRecord['label'] ?? '' }}
                                @if (! empty($action->existingRecord['status']))
                                    <span class="text-muted">— {{ $action->existingRecord['status'] }}</span>
                                @endif
                            </div>
                            @if (! empty($action->existingRecord['url']))
                                <a href="{{ $action->existingRecord['url'] }}" class="d-inline-block mt-1">
                                    {{ __('maternity_handoffs.cards.open_record') }}
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Read-only context block: what this action will act on. --}}
                    @if (! empty($action->context['summary']))
                        <dl class="row mb-3 g-0 small">
                            @foreach ($action->context['summary'] as $label => $value)
                                @if (filled($value))
                                    <dt class="col-5 fw-normal text-muted">{{ $label }}</dt>
                                    <dd class="col-7 mb-1">{{ $value }}</dd>
                                @endif
                            @endforeach
                        </dl>
                    @endif

                    @if (view()->exists($fieldsPartial))
                        @include($fieldsPartial, ['action' => $action])
                    @endif

                    @if ($action->requiresReason())
                        <div class="mb-2">
                            <label class="form-label" for="{{ $modalId }}Reason">
                                {{ __('maternity_handoffs.modal.reason') }}
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('reason') is-invalid @enderror"
                                      id="{{ $modalId }}Reason" name="reason" rows="2"
                                      required maxlength="500">{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    @foreach (($action->context['notices'] ?? []) as $notice)
                        <p class="text-muted fst-italic mb-1" style="font-size: .75rem;">{{ $notice }}</p>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        {{ $action->reusesExistingRecord()
                            ? __('maternity_handoffs.modal.confirm_open')
                            : __('maternity_handoffs.modal.confirm_handoff') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
