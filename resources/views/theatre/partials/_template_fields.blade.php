{{--
    Render dynamic procedure-template fields for a stage.
    Inputs:
      $stageTemplates (array keyed by template type) — provided by TheatreController@show
      $stage (string) — e.g. 'PRE_OP'
--}}
@php
    $tpl = $stageTemplates[$stage] ?? null;
@endphp
@if($tpl && (count($tpl['by_section'] ?? []) || count($tpl['ungrouped'] ?? [])))
    <hr>
    <h6 class="text-uppercase text-muted small mb-2">Template Fields</h6>
    @foreach($tpl['by_section'] as $sectionGroup)
        <div class="mb-2">
            <div class="fw-semibold small">{{ $sectionGroup['section']->name }}</div>
            @if($sectionGroup['section']->description)<div class="text-muted small mb-1">{{ $sectionGroup['section']->description }}</div>@endif
            <div class="row g-2">
                @foreach($sectionGroup['fields'] as $field)
                    @include('theatre.partials._template_field', ['field' => $field])
                @endforeach
            </div>
        </div>
    @endforeach
    @if(count($tpl['ungrouped'] ?? []))
        <div class="row g-2">
            @foreach($tpl['ungrouped'] as $field)
                @include('theatre.partials._template_field', ['field' => $field])
            @endforeach
        </div>
    @endif
@endif
