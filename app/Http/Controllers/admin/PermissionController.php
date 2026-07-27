<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('module')
            ->withCount(['roles', 'menus'])
            ->orderBy('code')
            ->get();

        $modules = Module::orderBy('name')->get();

        return view('admin.rbac.permissions.index', compact('permissions', 'modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:150', 'unique:permissions,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'new_module_name' => ['nullable', 'string', 'max:100'],
        ]);

        if (empty($data['module_id']) && !empty($data['new_module_name'])) {
            $module = Module::firstOrCreate(
                ['code' => Str::slug($data['new_module_name'])],
                ['name' => $data['new_module_name']]
            );
            $data['module_id'] = $module->id;
        }

        Permission::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'module_id' => $data['module_id'] ?? null,
        ]);

        return back()->with('success', 'Permission berhasil dibuat. Ingat: pasang code ini ke middleware route yang sesuai supaya benar-benar berlaku.');
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:150', Rule::unique('permissions', 'code')->ignore($permission->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'module_id' => ['nullable', 'exists:modules,id'],
        ]);

        $permission->update($data);

        return back()->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->exists() || $permission->menus()->exists()) {
            return back()->with('error', 'Permission masih dipakai di role atau menu, lepas dulu sebelum menghapus.');
        }

        $permission->delete();

        return back()->with('success', 'Permission berhasil dihapus.');
    }
}
