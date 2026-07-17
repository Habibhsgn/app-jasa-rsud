<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <span class="align-middle">Jasa RSUD</span>
        </a>

        @php
            $jsonString = file_get_contents(resource_path('data/menu.json'));
            $menus = json_decode($jsonString, true);

            /**
             * Whitelist akses menu per role.
             * Role yang TIDAK terdaftar di sini (misal 'admin') dianggap punya akses penuh.
             */
            $roleAccess = [
                'karu' => ['dashboard', 'karu.jasa', 'pegawai.index', 'index.scoring.index'],
                'koordinator_karu' => ['dashboard', 'karu.jasa', 'pegawai.index', 'index.scoring.index'],
                'manajemen' => ['dashboard', 'management.index.scoring.index', 'laporan.jasa.index'],
            ];

            $userRole = auth()->check() ? auth()->user()->role : null;
            $allowedRoutes = $roleAccess[$userRole] ?? null; // null = akses penuh (misal admin)

            /**
             * Kelompokkan menu per header, lalu tentukan visibility tiap item
             * berdasarkan whitelist role. Header hanya dirender jika section-nya
             * punya minimal 1 item yang visible.
             */
            $groups = [];
            $currentHeaderIndex = -1;

            foreach ($menus as $menu) {
                if (isset($menu['header'])) {
                    $groups[] = [
                        'header' => $menu['header'],
                        'items' => [],
                    ];
                    $currentHeaderIndex = count($groups) - 1;
                    continue;
                }

                $visible = is_null($allowedRoutes) || in_array($menu['route'], $allowedRoutes);

                if (!$visible) {
                    continue;
                }

                if ($currentHeaderIndex === -1) {
                    // Jaga-jaga kalau ada item sebelum header pertama
                    $groups[] = ['header' => null, 'items' => []];
                    $currentHeaderIndex = count($groups) - 1;
                }

                $groups[$currentHeaderIndex]['items'][] = $menu;
            }
        @endphp

        <ul class="sidebar-nav">
            @foreach ($groups as $group)
                @continue(empty($group['items']))

                @if ($group['header'])
                    <li class="sidebar-header">
                        {{ $group['header'] }}
                    </li>
                @endif

                @foreach ($group['items'] as $menu)
                    <li class="sidebar-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route($menu['route']) }}">
                            <i class="align-middle" data-feather="{{ $menu['icon'] }}"></i>
                            <span class="align-middle">{{ $menu['name'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
</nav>