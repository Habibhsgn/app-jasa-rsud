<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class userManagementControllers extends Controller
{
    public function index()
    {
        $users = User::with(['ruangan', 'role'])->where('id', '!=', Auth::id())->get();
        $roles = Role::where('code', '!=', 'admin')->orderBy('name')->get();

        return view('admin.userManage', compact('users', 'roles'));
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active; // Tukar status (true jadi false, vice versa)
        $user->save();

        return back()->with('success', 'Status user ' . $user->name . ' berhasil diubah.');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            // Validasi dinamis: role code harus benar-benar ada di tabel roles.
            // Sengaja tidak hardcode 'karu,koordinator_karu' lagi supaya otomatis
            // ikut kalau nanti ada role baru ditambah lewat /rbac/roles.
            'role' => ['required', 'exists:roles,code'],
        ]);

        $user = User::findOrFail($id);
        $role = Role::where('code', $request->role)->firstOrFail();

        $user->role_id = $role->id;
        $user->save();

        return back()->with('success', 'Role user ' . $user->name . ' berhasil diubah menjadi ' . strtoupper($role->code));
    }
}
