<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class userManagementControllers extends Controller
{
    public function index()
    {
        // Ambil semua user kecuali Admin yang sedang login (agar tidak menonaktifkan diri sendiri)
        $users = User::with('ruangan')->where('id', '!=', Auth::id())->get();
        return view('admin.userManage', compact('users'));
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active; // Tukar status (true jadi false, vice versa)
        $user->save();

        return back()->with('success', 'Status user ' . $user->name . ' berhasil diubah.');
    }
}