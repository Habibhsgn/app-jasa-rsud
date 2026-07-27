<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <span class="align-middle">Jasa RSUD</span>
        </a>

        @php
            /** @var \App\Models\User|null $user */
            $user = auth()
                ->user()
                ?->loadMissing(['role.permissions', 'permissionOverrides.permission']);

            $headers = \App\Models\Menu::with(['children.permissions'])
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        @endphp

        <ul class="sidebar-nav">
            @foreach ($headers as $header)
                @php
                    $visibleChildren = $header->children->filter(fn($child) => $child->isVisibleTo($user));
                @endphp

                @continue($visibleChildren->isEmpty())

                <li class="sidebar-header">
                    {{ $header->name }}
                </li>

                @foreach ($visibleChildren as $menu)
                    <li class="sidebar-item {{ request()->routeIs($menu->route_name) ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route($menu->route_name) }}">
                            <i class="align-middle" data-feather="{{ $menu->icon }}"></i>
                            <span class="align-middle">{{ $menu->name }}</span>
                        </a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
</nav>
