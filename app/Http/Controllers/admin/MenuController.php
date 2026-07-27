<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;

class MenuController extends Controller
{
    public function index()
    {
        $headers = Menu::with(['children.permissions'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        $allHeaders = Menu::whereNull('parent_id')->orderBy('order')->get();
        $modules = Module::orderBy('name')->get();
        $permissions = Permission::orderBy('code')->get();

        $routeNames = collect(RouteFacade::getRoutes())
            ->map(fn($r) => $r->getName())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.rbac.menus.index', compact('headers', 'allHeaders', 'modules', 'permissions', 'routeNames'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:menus,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'order' => ['nullable', 'integer'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $menu = Menu::create([
            'parent_id' => $data['parent_id'] ?? null,
            'module_id' => $data['module_id'] ?? null,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'route_name' => $data['route_name'] ?? null,
            'is_header' => $request->boolean('is_header'),
            'order' => $data['order'] ?? 0,
        ]);

        $menu->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Menu berhasil dibuat.');
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:menus,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'order' => ['nullable', 'integer'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $menu->update([
            'parent_id' => $data['parent_id'] ?? null,
            'module_id' => $data['module_id'] ?? null,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'route_name' => $data['route_name'] ?? null,
            'is_header' => $request->boolean('is_header'),
            'is_active' => $request->boolean('is_active', true),
            'order' => $data['order'] ?? 0,
        ]);

        $menu->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        if ($menu->children()->exists()) {
            return back()->with('error', 'Hapus dulu menu anak di bawah header ini sebelum menghapus header-nya.');
        }

        $menu->delete();

        return back()->with('success', 'Menu berhasil dihapus.');
    }
}
