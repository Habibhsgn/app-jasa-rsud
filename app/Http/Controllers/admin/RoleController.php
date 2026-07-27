<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('admin.rbac.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.rbac.roles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($data);

        return redirect()->route('rbac.roles.edit', $role)->with('success', 'Role berhasil dibuat, silakan atur permission-nya.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        $modules = Module::with(['permissions' => fn($q) => $q->orderBy('name')])
            ->orderBy('order')
            ->get();

        $unassigned = Permission::whereNull('module_id')->orderBy('name')->get();

        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.rbac.roles.edit', compact('role', 'modules', 'unassigned', 'rolePermissionIds'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'code')->ignore($role->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        // Role super (lihat Role::SUPER_ROLE_CODES) tetap lolos semua permission
        // check di kode apa pun isi pivot-nya, jadi checklist ini di role itu
        // hanya dokumentasi, bukan penentu akses sesungguhnya.
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('rbac.roles.index')->with('success', 'Role & permission berhasil disimpan.');
    }

    public function destroy(Role $role)
    {
        if ($role->isSuper()) {
            return back()->with('error', 'Role super/admin tidak boleh dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role masih dipakai oleh user, pindahkan user-nya dulu.');
        }

        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }
}
