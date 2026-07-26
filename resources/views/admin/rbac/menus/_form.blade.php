@php
    $menuPermissionIds = $menu?->permissions->pluck('id')->toArray() ?? [];
@endphp

<div class="mb-3">
    <label class="form-label">Tipe</label>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_header" value="1" id="isHeader{{ $menu?->id ?? 'new' }}"
            {{ $menu?->is_header ? 'checked' : '' }}
            onchange="document.getElementById('parentWrap{{ $menu?->id ?? 'new' }}').style.display = this.checked ? 'none' : 'block'">
        <label class="form-check-label" for="isHeader{{ $menu?->id ?? 'new' }}">
            Ini adalah <strong>header</strong> (judul grup, bukan link)
        </label>
    </div>
</div>

<div id="parentWrap{{ $menu?->id ?? 'new' }}" style="{{ $menu?->is_header ? 'display:none' : '' }}">
    <div class="mb-3">
        <label class="form-label">Header Induk</label>
        <select name="parent_id" class="form-select">
            <option value="">— tidak ada (jadi header baru) —</option>
            @foreach ($allHeaders as $h)
                <option value="{{ $h->id }}" {{ $menu?->parent_id == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Nama</label>
    <input type="text" name="name" class="form-control" value="{{ $menu->name ?? '' }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Icon (feather icon, mis. dollar-sign)</label>
    <input type="text" name="icon" class="form-control" value="{{ $menu->icon ?? '' }}">
</div>

<div class="mb-3">
    <label class="form-label">Route</label>
    <select name="route_name" class="form-select">
        <option value="">— tidak ada (khusus header) —</option>
        @foreach ($routeNames as $routeName)
            <option value="{{ $routeName }}" {{ ($menu->route_name ?? '') === $routeName ? 'selected' : '' }}>{{ $routeName }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Module (opsional, buat grouping admin)</label>
    <select name="module_id" class="form-select">
        <option value="">—</option>
        @foreach ($modules as $module)
            <option value="{{ $module->id }}" {{ ($menu->module_id ?? null) == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Urutan</label>
    <input type="number" name="order" class="form-control" value="{{ $menu->order ?? 0 }}">
</div>

@if ($menu)
    <div class="mb-3 form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $menu->id }}" {{ $menu->is_active ? 'checked' : '' }}>
        <label class="form-check-label" for="active{{ $menu->id }}">Aktif (tampil di sidebar)</label>
    </div>
@endif

<div class="mb-2">
    <label class="form-label">Permission yang membuat menu ini terlihat</label>
    <small class="d-block text-muted mb-2">Kosongkan semua kalau menu terbuka untuk semua user login. Kalau dicentang lebih dari satu, user cukup punya SALAH SATU untuk melihat menu ini.</small>
    <div style="max-height:180px; overflow-y:auto; border:1px solid #dee2e6; border-radius:.375rem; padding:.5rem;">
        @foreach ($permissions as $permission)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                    id="menuperm-{{ $menu?->id ?? 'new' }}-{{ $permission->id }}"
                    {{ in_array($permission->id, $menuPermissionIds) ? 'checked' : '' }}>
                <label class="form-check-label" for="menuperm-{{ $menu?->id ?? 'new' }}-{{ $permission->id }}">
                    {{ $permission->name }} <small class="text-muted">({{ $permission->code }})</small>
                </label>
            </div>
        @endforeach
    </div>
</div>
