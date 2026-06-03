{{--
    Reusable clinical sections partial.
    Variables:
        $r  — MedicalRecord|null  (the record for this session)
--}}
@if(!$r)
<p class="text-muted small mb-0 p-3">
    <i class="ti ti-notes-off me-1"></i>No consultation record has been started for this session.
</p>
@else

{{-- ── COMPLAINTS ──────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-message-report me-1 text-primary"></i>Complaints <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $r->complaints->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($r->complaints as $c)
        <div class="border-start border-3 ps-3 mb-2" style="border-color:{{ $c->severity === 'severe' ? '#dc3545' : ($c->severity === 'moderate' ? '#fd7e14' : '#ffc107') }} !important">
            <span>{{ $c->description }}</span>
            @if($c->duration) <small class="text-muted ms-1">({{ $c->duration }})</small> @endif
            @if($c->severity) <span class="badge bg-{{ $c->severity === 'severe' ? 'danger' : ($c->severity === 'moderate' ? 'warning' : 'info') }} ms-1">{{ ucfirst($c->severity) }}</span> @endif
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None recorded.</p>
        @endforelse
    </div>
</div>

{{-- ── DIAGNOSES ───────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-report-medical me-1 text-success"></i>Diagnoses <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $r->diagnoses->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($r->diagnoses as $d)
        <div class="border-start border-3 border-success ps-3 mb-2">
            <span class="fw-medium">{{ $d->description }}</span>
            @if($d->icd_code) <code class="ms-1 small">{{ $d->icd_code }}</code> @endif
            <span class="badge bg-{{ $d->type === 'final' ? 'success' : 'warning' }} ms-1">{{ ucfirst($d->type) }}</span>
            @if($d->is_primary) <span class="badge bg-warning text-dark ms-1"><i class="ti ti-star-filled me-1"></i>Primary</span> @endif
            @if($d->notes) <div><small class="text-muted">{{ $d->notes }}</small></div> @endif
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None recorded.</p>
        @endforelse
    </div>
</div>

{{-- ── TREATMENTS ──────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-vaccine me-1 text-info"></i>Treatments <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $r->treatments->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($r->treatments as $t)
        <div class="border-start border-3 border-info ps-3 mb-2">
            <span class="fw-medium">{{ $t->name }}</span>
            @if($t->description) <div><small class="text-muted">{{ $t->description }}</small></div> @endif
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None recorded.</p>
        @endforelse
    </div>
</div>

{{-- ── PRESCRIPTIONS ───────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-prescription me-1"></i>Prescriptions <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $r->prescriptions->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($r->prescriptions as $rx)
        <div class="border rounded p-2 mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-medium small">{{ $rx->prescription_number }}</span>
                <x-status-badge :status="$rx->status" />
            </div>
            <div class="table-responsive"><table class="table table-sm table-borderless mb-0" style="font-size:.8rem">
                <thead><tr class="text-muted"><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th></tr></thead>
                <tbody>
                    @foreach($rx->items as $item)
                    <tr>
                        <td>{{ $item->drug_name }}</td>
                        <td>{{ $item->dosage }}</td>
                        <td>{{ $item->frequency }}</td>
                        <td>{{ $item->duration }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None recorded.</p>
        @endforelse
    </div>
</div>

{{-- ── TASKS ───────────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-checklist me-1"></i>Tasks <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $r->tasks->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($r->tasks as $task)
        <div class="d-flex align-items-center gap-2 mb-2">
            @if($task->status === 'done')
                <i class="ti ti-circle-check text-success"></i>
            @elseif($task->status === 'in_progress')
                <i class="ti ti-clock text-warning"></i>
            @else
                <i class="ti ti-circle text-secondary"></i>
            @endif
            <div>
                <span class="{{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</span>
                @if($task->note) <div><small class="text-muted">{{ $task->note }}</small></div> @endif
            </div>
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None assigned.</p>
        @endforelse
    </div>
</div>

@endif {{-- end $r check --}}
