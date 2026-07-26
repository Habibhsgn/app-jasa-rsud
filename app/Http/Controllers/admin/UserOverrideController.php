<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Http\Request;

class UserOverrideController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role')
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rbac.overrides.index', compact('users'));
    }

    public function edit(User $user)
    {
        $user->load(['role.permissions', 'permissionOverrides']);

        $permissions = Permission::with('module')->orderBy('code')->get();
        $overrides = $user->permissionOverrides->keyBy('permission_id');
        $rolePermissionIds = $user->role?->permissions->pluck('id')->toArray() ?? [];

        return view('admin.rbac.overrides.edit', compact('user', 'permissions', 'overrides', 'rolePermissionIds'));
    }

    public function update(Request $request, User $user)
    {
        // state[<permission_id>] = 'inherit' | 'grant' | 'revoke'
        $data = $request->validate([
            'state' => ['array'],
            'state.*' => ['in:inherit,grant,revoke'],
        ]);

        foreach ($data['state'] ?? [] as $permissionId => $state) {
            if ($state === 'inherit') {
                UserPermissionOverride::where('user_id', $user->id)
                    ->where('permission_id', $permissionId)
                    ->delete();
                continue;
            }

            UserPermissionOverride::updateOrCreate(
                ['user_id' => $user->id, 'permission_id' => $permissionId],
                ['type' => $state]
            );
        }

        return redirect()->route('rbac.overrides.edit', $user)->with('success', 'Override permission user berhasil disimpan.');
    }
}
