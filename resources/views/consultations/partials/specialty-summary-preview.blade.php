<div class="specialty-summary-preview">
    <h6 class="fw-bold mb-2">{{ $title }}</h6>
    @if(! empty($warnings))
        <div class="alert alert-warning py-2 small">
            @foreach($warnings as $warning)
                <div>{{ $warning }}</div>
            @endforeach
        </div>
    @endif
    @forelse($sections as $section)
        @if(! empty($section['content']))
            <div class="mb-2">
                <div class="small fw-semibold text-muted">{{ $section['label'] }}</div>
                <div>{{ $section['content'] }}</div>
            </div>
        @endif
    @empty
        <div class="text-muted small">{{ __('consultation_specialties.summary_builder.no_data_available') }}</div>
    @endforelse
</div>
