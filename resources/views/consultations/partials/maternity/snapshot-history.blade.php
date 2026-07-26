{{--
    Phase 14R.6.1 — read-only completion-snapshot history.

    Bounded metadata only. Every link is a GET to a version scoped to THIS
    consultation; there is no form, no POST/PATCH/DELETE, and selecting a
    version performs no write and does not verify against live maternity data.

    Expects: $vm
--}}
@php($vm = $vm ?? null)

@if ($vm && $vm->hasHistory())
    <div class="mt-3" id="maternity-snapshot-history">
        <div class="fw-semibold small mb-1">
            {{ __('consultation_maternity_summary.snapshot.historical_versions') }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size: .78rem;">
                <thead>
                    <tr>
                        <th>{{ __('consultation_maternity_summary.summary.snapshot_version') }}</th>
                        <th>{{ __('consultation_maternity_summary.summary.captured_at') }}</th>
                        <th>{{ __('consultation_maternity_summary.summary.captured_by') }}</th>
                        <th>{{ __('consultation_maternity_summary.summary.schema_version') }}</th>
                        <th>{{ __('consultation_maternity_summary.snapshot.integrity_verification') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vm->history as $row)
                        <tr @class(['table-light' => $row['version'] === $vm->snapshotVersion])>
                            <td>
                                v{{ $row['version'] }}
                                @if ($loop->first)
                                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle ms-1">
                                        {{ __('consultation_maternity_summary.snapshot.latest') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $row['captured_at'] }}</td>
                            <td>{{ $row['captured_by'] ?? '—' }}</td>
                            <td>{{ $row['schema_version'] }}</td>
                            <td>
                                @if ($row['integrity'] === \App\Data\Consultation\Maternity\ConsultationMaternitySummaryViewModel::INTEGRITY_VERIFIED)
                                    <span class="text-success">{{ __('consultation_maternity_summary.snapshot.verified_short') }}</span>
                                @elseif ($row['integrity'] === \App\Data\Consultation\Maternity\ConsultationMaternitySummaryViewModel::INTEGRITY_MISMATCH)
                                    <span class="text-danger fw-semibold">{{ __('consultation_maternity_summary.snapshot.mismatch_short') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($vm->route('history') && $row['version'] !== $vm->snapshotVersion)
                                    {{-- GET only. Scoped server-side to this consultation. --}}
                                    <a class="btn btn-sm btn-link p-0"
                                       href="{{ $vm->route('history') }}?version={{ $row['version'] }}">
                                        {{ __('maternity_handoffs.cards.open_record') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($vm->previousSnapshotVersion || $vm->nextSnapshotVersion)
            <div class="d-flex gap-2 mt-2">
                @if ($vm->previousSnapshotVersion && $vm->route('history'))
                    <a class="btn btn-sm btn-outline-secondary"
                       href="{{ $vm->route('history') }}?version={{ $vm->previousSnapshotVersion }}">
                        &larr; {{ __('consultation_maternity_summary.snapshot.previous') }}
                    </a>
                @endif
                @if ($vm->nextSnapshotVersion && $vm->route('history'))
                    <a class="btn btn-sm btn-outline-secondary"
                       href="{{ $vm->route('history') }}?version={{ $vm->nextSnapshotVersion }}">
                        {{ __('consultation_maternity_summary.snapshot.next') }} &rarr;
                    </a>
                @endif
            </div>
        @endif
    </div>
@endif
