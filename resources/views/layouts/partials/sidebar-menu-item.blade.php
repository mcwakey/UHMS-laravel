@if(!empty($item['children']))
    <li class="submenu {{ !empty($item['active']) ? 'active' : '' }}">
        <a href="javascript:void(0);" class="{{ !empty($item['active']) ? 'active subdrop' : '' }}">
            @if(!empty($item['icon']))
                <i class="{{ $item['icon'] }}"></i>
            @endif
            <span>{{ $item['label'] }}</span>
            @if(!empty($item['badge']))
                <span class="{{ $item['badge_class'] ?? 'badge bg-danger rounded-pill ms-auto' }}">{{ $item['badge'] }}</span>
            @endif
            <span class="menu-arrow"></span>
        </a>
        <ul>
            @foreach($item['children'] as $child)
                @include('layouts.partials.sidebar-menu-item', ['item' => $child])
            @endforeach
        </ul>
    </li>
@else
    <li class="{{ !empty($item['active']) ? 'active' : '' }}">
        <a href="{{ route($item['route']) }}" class="{{ !empty($item['active']) ? 'active' : '' }}">
            @if(!empty($item['icon']))
                <i class="{{ $item['icon'] }}"></i>
            @endif
            <span>{{ $item['label'] }}</span>
            @if(!empty($item['badge']))
                <span class="{{ $item['badge_class'] ?? 'badge bg-danger rounded-pill ms-auto' }}">{{ $item['badge'] }}</span>
            @endif
        </a>
    </li>
@endif