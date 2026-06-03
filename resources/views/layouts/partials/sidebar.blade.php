<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ URL::asset('build/img/logo.svg') }}" alt="UHMS">
            </a>
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ URL::asset('build/img/logo-small.svg') }}" alt="UHMS">
            </a>
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ URL::asset('build/img/logo-white.svg') }}" alt="UHMS">
            </a>
        </div>
        <button aria-label="Back" title="Back" class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-left"></i>
        </button>
        <button aria-label="Close" title="Close" class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                @foreach($sidebarSections ?? [] as $section)
                    @if(!empty($section['title']))
                        <li class="menu-title"><span>{{ $section['title'] }}</span></li>
                    @endif
                    <li><ul>
                        @foreach($section['items'] as $item)
                            @include('layouts.partials.sidebar-menu-item', ['item' => $item])
                        @endforeach
                    </ul></li>
                @endforeach

            </ul>
        </div>
    </div>
    <!-- /Sidenav Menu -->

</div>
<!-- Sidenav Menu End -->
