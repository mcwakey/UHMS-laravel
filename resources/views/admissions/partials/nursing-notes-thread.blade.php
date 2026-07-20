@include('admissions.partials.nursing-chat-styles')

@php
    $notes = collect($notes ?? [])->sortBy(fn ($note) => $note->observed_at ?? $note->created_at)->values();
    $currentUserId = auth()->id();
    $initialsFor = function ($user) {
        $name = trim((string) ($user?->name ?? $user?->full_name ?? ''));
        if ($name === '') {
            return 'N';
        }

        $parts = preg_split('/\s+/', $name);
        return strtoupper(mb_substr($parts[0] ?? 'N', 0, 1).mb_substr($parts[count($parts) - 1] ?? '', 0, 1));
    };
@endphp

@forelse($notes as $note)
    @php
        $author = $note->nurse ?? $note->createdBy;
        $isMine = $currentUserId && in_array((int) $currentUserId, array_filter([(int) ($note->nurse_id ?? 0), (int) ($note->created_by ?? 0)]), true);
        $stamp = $note->observed_at ?? $note->created_at;
    @endphp
    <div @class(['nursing-message', 'nursing-message--mine' => $isMine])>
        <div class="nursing-message__avatar">{{ $initialsFor($author) }}</div>
        <div class="nursing-message__bubble">
            <div class="nursing-message__meta">
                <span>
                    <span class="badge badge-soft-info me-1">{{ $note->note_type?->label() ?? __('admissions.general') }}</span>
                    <strong>{{ $author->name ?? $author->full_name ?? __('admissions.general') }}</strong>
                </span>
                <span>{{ $stamp?->format('d M Y H:i') }}</span>
            </div>
            <div class="nursing-message__body">{{ $note->note }}</div>
        </div>
    </div>
@empty
    <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_nursing_notes') }}</div>
@endforelse
