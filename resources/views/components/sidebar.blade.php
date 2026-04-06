<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <span class="align-middle">Jasa RSUD</span>
        </a>

        @php
            // 1. Ambil data secara elegan dari file resources/data/menus.json
            $jsonString = file_get_contents(resource_path('data/menu.json'));
            $menus = json_decode($jsonString, true);

            // 2. Daftar rute yang DIIZINKAN untuk dilihat oleh KARU
            $aksesKaru = ['dashboard', 'karu.jasa', 'pegawai.index'];
        @endphp

        <ul class="sidebar-nav">
            @foreach ($menus as $menu)
                
                @if (isset($menu['header']))
                    <li class="sidebar-header">
                        {{ $menu['header'] }}
                    </li>
                @else
                    {{-- Filter akses KARU --}}
                    @if (auth()->check() && auth()->user()->role === 'karu' && !in_array($menu['route'], $aksesKaru))
                        @continue
                    @endif

                    <li class="sidebar-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route($menu['route']) }}">
                            <i class="align-middle" data-feather="{{ $menu['icon'] }}"></i> 
                            <span class="align-middle">{{ $menu['name'] }}</span>
                        </a>
                    </li>
                @endif

            @endforeach
        </ul>
    </div>
</nav>