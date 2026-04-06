<nav class="navbar navbar-expand navbar-light navbar-bg">
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">
            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#"
                    data-bs-toggle="dropdown">
                    <i class="align-middle" data-feather="settings"></i>
                </a>

                <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#"
                    data-bs-toggle="dropdown">
                    <img src="{{ asset('assets/img/avatars/avatar.png') }}" class="avatar img-fluid rounded me-1" alt="Avatar" />
                    {{-- Menampilkan nama user yang sedang login secara dinamis --}}
                    <span class="text-dark">{{ Auth::user()->name ?? 'User' }}</span>
                </a>
                
                <div class="dropdown-menu dropdown-menu-end">
                    {{-- Form POST untuk Logout yang aman --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="align-middle me-1" data-feather="log-out"></i> Log out
                        </a>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>