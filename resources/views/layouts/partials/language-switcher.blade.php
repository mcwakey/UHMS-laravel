<div class="header-item">
    <div class="dropdown me-3">
        <button
            class="topbar-link btn btn-icon topbar-link dropdown-toggle drop-arrow-none"
            data-bs-toggle="dropdown"
            data-bs-offset="0,24"
            type="button"
            aria-haspopup="true"
            aria-expanded="false"
            aria-label="{{ __('common.language') }}"
            title="{{ __('common.language') }}"
        >
            <span class="fs-16" aria-hidden="true">{{ app()->getLocale() === 'fr' ? '🇫🇷' : '🇬🇧' }}</span>
            <span class="visually-hidden">{{ __('common.language') }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-2">
            <form method="POST" action="{{ route('locale.switch') }}" data-spa-ignore="true" class="mb-0">
                @csrf
                <input type="hidden" name="locale" value="en">
                <button type="submit" class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    <span class="me-2" aria-hidden="true">🇬🇧</span>
                    <span>{{ __('common.english') }}</span>
                    @if(app()->getLocale() === 'en')<i class="ti ti-check ms-auto"></i>@endif
                </button>
            </form>
            <form method="POST" action="{{ route('locale.switch') }}" data-spa-ignore="true" class="mb-0">
                @csrf
                <input type="hidden" name="locale" value="fr">
                <button type="submit" class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'fr' ? 'active' : '' }}">
                    <span class="me-2" aria-hidden="true">🇫🇷</span>
                    <span>{{ __('common.french') }}</span>
                    @if(app()->getLocale() === 'fr')<i class="ti ti-check ms-auto"></i>@endif
                </button>
            </form>
        </div>
    </div>
</div>
