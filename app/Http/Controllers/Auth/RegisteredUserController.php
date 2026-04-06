<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\Ruangan;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Cari ID ruangan yang sudah diklaim oleh user lain
        $ruanganTerpakai = User::whereNotNull('ruangan_id')->pluck('ruangan_id');

        // Ambil data ruangan yang ID-nya TIDAK ADA di daftar terpakai
        $ruanganTersedia = Ruangan::whereNotIn('id', $ruanganTerpakai)
            ->orderBy('nama_ruangan', 'asc')
            ->get();

        // Lempar datanya ke tampilan register
        return view('auth.register', compact('ruanganTersedia'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */


    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'ruangan_id' => ['required', 'exists:ruangan,id'], // <-- Wajib diisi & harus ada di tabel ruangan
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'karu', // Otomatis jadi KARU
            'ruangan_id' => $request->ruangan_id, // <-- Simpan ID ruangan ke tabel users
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
