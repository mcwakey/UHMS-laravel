@php
    $entry = $specialtyEntries[$section['key']] ?? [];
    $fields = $section['form_fields'] ?? [];
@endphp

<div class="tab-pane fade {{ ($activeTabTarget ?? null) === ($section['tab_target'] ?? null) ? 'show active' : '' }}" id="{{ $section['tab_target'] }}" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">
                <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} me-1"></i>{{ $section['translated_label'] ?? $section['label'] }}
            </h6>
            <div class="d-flex gap-1">
                <span class="badge bg-info-subtle text-info">{{ __('consultation_specialties.workspace.specialist_section') }}</span>
                @if($section['is_required'] ?? false)
                    <span class="badge bg-warning text-dark">{{ __('consultation_specialties.workspace.required') }}</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form data-ajax-form="{{ $section['key'] }}" data-consultation-form="specialty-entry" data-refresh-section="{{ $section['key'] }}" data-route-context-required="true" method="POST" action="{{ route('admin.consultations.specialty-entries.store', [$visit, $section['key']]) }}">
                @csrf
                <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute?->id ?? '' }}">
                <input type="hidden" name="specialty_profile_id" value="{{ $specialtyLayout['profile']['id'] ?? '' }}">
                <x-consultation-idempotency-key :action="'specialty-entry.'.$section['key']" />

                <div class="row g-3">
                    @foreach($fields as $field)
                        @php
                            $name = $field['name'];
                            $type = $field['type'] ?? 'text';
                            $value = old($name, $entry[$name] ?? null);
                            $label = __('consultation_specialties.forms.fields.'.$name);
                        @endphp
                        <div class="{{ in_array($type, ['textarea', 'array'], true) ? 'col-12' : 'col-md-6' }}">
                            @if($type === 'boolean')
                                <div class="form-check mt-4">
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" id="{{ $section['key'].'_'.$name }}" @checked((bool) $value)>
                                    <label class="form-check-label" for="{{ $section['key'].'_'.$name }}">{{ $label }}</label>
                                </div>
                            @else
                                <label class="form-label small" for="{{ $section['key'].'_'.$name }}">{{ $label }}</label>
                                @if($type === 'textarea')
                                    <textarea name="{{ $name }}" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3">{{ $value }}</textarea>
                                @elseif($type === 'array')
                                    <textarea name="{{ $name }}[]" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3" placeholder="{{ __('consultation_specialties.forms.array_hint') }}">{{ collect($value)->implode("\n") }}</textarea>
                                @else
                                    <input type="{{ $type }}" name="{{ $name }}" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" value="{{ $value }}" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif>
                                @endif
                                @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">{{ $entry ? __('consultation_specialties.messages.entry_loaded') : __('consultation_specialties.messages.no_entry_yet') }}</small>
                    <div class="d-flex gap-2">
                        @if($entry)
                            <button type="submit" form="delete-specialty-{{ $section['key'] }}" class="btn btn-outline-danger btn-sm" data-confirm="{{ __('consultation_specialties.messages.confirm_delete') }}">
                                <i class="ti ti-trash me-1"></i>{{ __('common.delete') }}
                            </button>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('consultation_specialties.actions.save_section') }}
                        </button>
                    </div>
                </div>
            </form>
            @if($entry)
                <form id="delete-specialty-{{ $section['key'] }}" method="POST" action="{{ route('admin.consultations.specialty-entries.destroy', [$visit, $section['key']]) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute?->id ?? '' }}">
                </form>
            @endif
        </div>
    </div>
</div>
