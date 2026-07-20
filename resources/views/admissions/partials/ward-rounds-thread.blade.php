@include('admissions.partials.nursing-chat-styles')

@php
    $rounds = collect($rounds ?? [])->sortBy(fn ($round) => $round->round_date ?? $round->created_at)->values();
    $currentUserId = auth()->id();
    $initialsFor = function ($user) {
        $name = trim((string) ($user?->name ?? $user?->full_name ?? ''));
        if ($name === '') {
            return 'R';
        }

        $parts = preg_split('/\s+/', $name);
        return strtoupper(mb_substr($parts[0] ?? 'R', 0, 1).mb_substr($parts[count($parts) - 1] ?? '', 0, 1));
    };
@endphp

@forelse($rounds as $round)
    @php
        $author = $round->recordedBy;
        $isMine = $currentUserId && (int) $currentUserId === (int) ($round->recorded_by ?? 0);
        $stamp = $round->round_date ?? $round->created_at;
    @endphp
    <div @class(['nursing-message', 'nursing-message--mine' => $isMine])>
        <div class="nursing-message__avatar">{{ $initialsFor($author) }}</div>
        <div class="nursing-message__bubble">
            <div class="nursing-message__meta">
                <span>
                    <span class="badge badge-soft-primary me-1">{{ __('admissions.ward_round') }}</span>
                    <strong>{{ $author->name ?? $author->full_name ?? __('admissions.general') }}</strong>
                </span>
                <span>{{ $stamp?->format('d M Y H:i') }}</span>
            </div>
            <div class="nursing-message__body">{{ $round->notes }}</div>
            @if($round->instructions)
                <div class="mt-2 pt-2 border-top small">
                    <span class="fw-semibold">{{ __('admissions.instructions_col') }}:</span>
                    <span class="nursing-message__body">{{ $round->instructions }}</span>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_ward_rounds_yet') }}</div>
@endforelse
