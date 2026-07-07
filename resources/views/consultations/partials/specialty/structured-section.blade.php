@php
    $fields = $section['form_fields'] ?? [];
    $entries = collect(($specialtyEntryGroups ?? [])[$section['key']] ?? []);
    $entryCount = $entries->count();
    $formId = 'add-specialty-'.$section['key'].'-form';
    $fieldLabel = fn (string $name) => \Illuminate\Support\Facades\Lang::has('consultation_specialties.forms.fields.'.$name)
        ? __('consultation_specialties.forms.fields.'.$name)
        : str($name)->replace('_', ' ')->title()->toString();
    $displayValue = function ($value): string {
        if (is_bool($value)) {
            return $value ? __('common.yes') : __('common.no');
        }

        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => filled($item))->implode(', ');
        }

        return (string) $value;
    };
    $entryTitle = function (array $entry) use ($fields, $fieldLabel, $displayValue, $section): string {
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = $entry[$name] ?? null;

            if (filled($value) || is_bool($value)) {
                return $fieldLabel($name).': '.$displayValue($value);
            }
        }

        return $section['translated_label'] ?? $section['label'];
    };
    $entryOwnerGroups = $ownerGroups($entries);
@endphp

<div class="tab-pane fade {{ ($activeTabTarget ?? null) === ($section['tab_target'] ?? null) ? 'show active' : '' }}" id="{{ $section['tab_target'] }}" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">
                <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} me-1"></i>{{ $section['translated_label'] ?? $section['label'] }}
            </h6>
            <div class="d-flex align-items-center gap-1">
                <span class="badge bg-light text-dark border">{{ $entryCount }} {{ Str::plural('entry', $entryCount) }}</span>
                <span class="badge bg-info-subtle text-info">{{ __('consultation_specialties.workspace.specialist_section') }}</span>
                @if($section['is_required'] ?? false)
                    <span class="badge bg-warning text-dark">{{ __('consultation_specialties.workspace.required') }}</span>
                @endif
                @can('consultations.create')
                    <button class="btn btn-sm btn-primary ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $formId }}" aria-expanded="false" aria-controls="{{ $formId }}">
                        <i class="ti ti-plus me-1"></i>{{ __('common.add') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @can('consultations.create')
            <div class="collapse mb-3" id="{{ $formId }}">
                <div class="card card-body bg-light">
                    <form data-ajax-form="{{ $section['key'] }}" data-consultation-form="specialty-entry" data-refresh-section="{{ $section['key'] }}" data-route-context-required="true" method="POST" action="{{ route('admin.consultations.specialty-entries.store', [$visit, $section['key']]) }}">
                        @csrf
                        <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute?->id ?? '' }}">
                        <input type="hidden" name="specialty_profile_id" value="{{ $specialtyLayout['profile']['id'] ?? '' }}">
                        <x-consultation-idempotency-key :action="'specialty-entry.'.$section['key'].'.create'" />

                        <div class="row g-3">
                            @foreach($fields as $field)
                                @php
                                    $name = $field['name'];
                                    $type = $field['type'] ?? 'text';
                                    $label = $fieldLabel($name);
                                @endphp
                                <div class="{{ in_array($type, ['textarea', 'array'], true) ? 'col-12' : 'col-md-6' }}">
                                    @if($type === 'boolean')
                                        <div class="form-check mt-4">
                                            <input type="hidden" name="{{ $name }}" value="0">
                                            <input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" id="{{ $section['key'].'_'.$name }}">
                                            <label class="form-check-label" for="{{ $section['key'].'_'.$name }}">{{ $label }}</label>
                                        </div>
                                    @else
                                        <label class="form-label small" for="{{ $section['key'].'_'.$name }}">{{ $label }}</label>
                                        @if($type === 'textarea')
                                            <textarea name="{{ $name }}" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3"></textarea>
                                        @elseif($type === 'array')
                                            <textarea name="{{ $name }}[]" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3" placeholder="{{ __('consultation_specialties.forms.array_hint') }}"></textarea>
                                        @else
                                            <input type="{{ $type }}" name="{{ $name }}" id="{{ $section['key'].'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif>
                                        @endif
                                        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-device-floppy me-1"></i>{{ __('consultation_specialties.actions.save_section') }}
                            </button>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#{{ $formId }}">{{ __('common.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            @forelse($entryOwnerGroups as $group)
                @php
                    $firstEntry = $group->first();
                @endphp
                <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                    <div class="owner-group-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                            <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                        </div>
                        <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                    </div>
                    @foreach($group as $specialtyEntry)
                        @php
                            $entry = $specialtyEntry->entry ?? [];
                            $editFormId = 'edit-specialty-'.$section['key'].'-'.$specialtyEntry->id.'-form';
                        @endphp
                        <div class="ehr-item" id="specialty-entry-{{ $specialtyEntry->id }}" data-owner-key="{{ $ownerKey($specialtyEntry) }}">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <p class="mb-1">{{ $entryTitle($entry) }}</p>
                                    <div class="row g-2">
                                        @foreach($fields as $field)
                                            @php
                                                $name = $field['name'];
                                                $value = $entry[$name] ?? null;
                                            @endphp
                                            @if(filled($value) || is_bool($value))
                                                <div class="col-md-6">
                                                    <small class="text-muted">{{ $fieldLabel($name) }}: {{ $displayValue($value) }}</small>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    @if($entryFooter($specialtyEntry))<small class="text-muted d-block">{{ $entryFooter($specialtyEntry) }}</small>@endif
                                </div>
                                <div class="entry-actions d-flex gap-1">
                                    @if($canEditEntry($specialtyEntry))
                                        <button aria-label="{{ __('consultation_specialties.admin.edit') }}" title="{{ __('consultation_specialties.admin.edit') }}" type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#{{ $editFormId }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    @endif
                                    @if($canDeleteEntry($specialtyEntry))
                                        <button type="submit" form="delete-specialty-{{ $specialtyEntry->id }}" class="btn btn-xs btn-outline-danger" data-confirm="{{ __('consultation_specialties.messages.confirm_delete') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @can('consultations.create')
                            <div class="collapse mt-3" id="{{ $editFormId }}">
                                <div class="card card-body bg-light border-0 p-3">
                                    <form data-ajax-form="{{ $section['key'] }}" data-consultation-form="specialty-entry" data-refresh-section="{{ $section['key'] }}" data-route-context-required="true" method="POST" action="{{ route('admin.consultations.specialty-entries.store', [$visit, $section['key']]) }}">
                                        @csrf
                                        <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute?->id ?? '' }}">
                                        <input type="hidden" name="specialty_profile_id" value="{{ $specialtyLayout['profile']['id'] ?? '' }}">
                                        <input type="hidden" name="consultation_specialty_entry_id" value="{{ $specialtyEntry->id }}">
                                        <x-consultation-idempotency-key :action="'specialty-entry.'.$section['key'].'.'.$specialtyEntry->id.'.update'" />

                                        <div class="row g-3">
                                            @foreach($fields as $field)
                                                @php
                                                    $name = $field['name'];
                                                    $type = $field['type'] ?? 'text';
                                                    $value = old($name, $entry[$name] ?? null);
                                                    $label = $fieldLabel($name);
                                                @endphp
                                                <div class="{{ in_array($type, ['textarea', 'array'], true) ? 'col-12' : 'col-md-6' }}">
                                                    @if($type === 'boolean')
                                                        <div class="form-check mt-4">
                                                            <input type="hidden" name="{{ $name }}" value="0">
                                                            <input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" id="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}" @checked((bool) $value)>
                                                            <label class="form-check-label" for="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}">{{ $label }}</label>
                                                        </div>
                                                    @else
                                                        <label class="form-label small" for="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}">{{ $label }}</label>
                                                        @if($type === 'textarea')
                                                            <textarea name="{{ $name }}" id="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3">{{ $value }}</textarea>
                                                        @elseif($type === 'array')
                                                            <textarea name="{{ $name }}[]" id="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" rows="3" placeholder="{{ __('consultation_specialties.forms.array_hint') }}">{{ collect($value)->implode("\n") }}</textarea>
                                                        @else
                                                            <input type="{{ $type }}" name="{{ $name }}" id="{{ $section['key'].'_'.$specialtyEntry->id.'_'.$name }}" class="form-control form-control-sm @error($name) is-invalid @enderror" value="{{ $value }}" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif>
                                                        @endif
                                                        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-3 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="ti ti-device-floppy me-1"></i>{{ __('consultation_specialties.actions.save_section') }}
                                            </button>
                                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#{{ $editFormId }}">{{ __('common.cancel') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endcan

                            @if($canDeleteEntry($specialtyEntry))
                                <form id="delete-specialty-{{ $specialtyEntry->id }}" method="POST" action="{{ route('admin.consultations.specialty-entries.destroy', [$visit, $section['key']]) }}" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute?->id ?? '' }}">
                                    <input type="hidden" name="consultation_specialty_entry_id" value="{{ $specialtyEntry->id }}">
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} fs-1 d-block mb-2"></i>
                    {{ __('consultation_specialties.messages.no_entry_yet') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
