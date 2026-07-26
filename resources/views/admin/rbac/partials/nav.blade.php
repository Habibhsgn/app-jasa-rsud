<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rbac.modules.*') ? 'active' : '' }}" href="{{ route('rbac.modules.index') }}">Module</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rbac.permissions.*') ? 'active' : '' }}" href="{{ route('rbac.permissions.index') }}">Permission</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rbac.roles.*') ? 'active' : '' }}" href="{{ route('rbac.roles.index') }}">Role</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rbac.menus.*') ? 'active' : '' }}" href="{{ route('rbac.menus.index') }}">Menu</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rbac.overrides.*') ? 'active' : '' }}" href="{{ route('rbac.overrides.index') }}">Override User</a>
    </li>
</ul>
