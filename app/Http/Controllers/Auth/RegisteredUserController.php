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
use App\Models\MasterBidang;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $ruanganTerpakai = User::whereNotNull('ruangan_id')
            ->pluck('ruangan_id');

        $ruanganTersedia = Ruangan::whereNotIn('id', $ruanganTerpakai)
            ->orderBy('nama_ruangan')
            ->get();

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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            'role' => ['required', 'in:karu,manajemen'],

            'ruangan_id' => [
                'required_if:role,karu',
                'nullable',
                'exists:ruangan,id',
            ],

            'kode_bidang' => [
                'required_if:role,manajemen',
                'nullable',
                'string',
            ],
        ]);

        $ruanganId = null;
        $bidangId = null;

        /*
    |--------------------------------------------------------------------------
    | Registrasi Karu
    |--------------------------------------------------------------------------
    */

        if ($request->role === 'karu') {

            // Pastikan ruangan belum dipakai
            $sudahDipakai = User::where('ruangan_id', $request->ruangan_id)->exists();

            if ($sudahDipakai) {
                return back()
                    ->withErrors([
                        'ruangan_id' => 'Ruangan tersebut sudah memiliki Kepala Ruangan.'
                    ])
                    ->withInput();
            }

            $ruanganId = $request->ruangan_id;
        }

        /*
    |--------------------------------------------------------------------------
    | Registrasi Manajemen
    |--------------------------------------------------------------------------
    */

        if ($request->role === 'manajemen') {

            $bidang = MasterBidang::whereRaw('UPPER(kode_bidang) = ?', [
                strtoupper($request->kode_bidang)
            ])
                ->where('is_active', true)
                ->first();

            if (!$bidang) {
                return back()
                    ->withErrors([
                        'kode_bidang' => 'Kode bidang tidak valid.'
                    ])
                    ->withInput();
            }

            if (User::where('bidang_id', $bidang->id)->exists()) {
                return back()
                    ->withErrors([
                        'kode_bidang' => 'Bidang tersebut sudah memiliki akun manajemen.'
                    ])
                    ->withInput();
            }
        }

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),

            'role'       => $request->role,

            'ruangan_id' => $request->role === 'karu'
                ? $request->ruangan_id
                : null,

            'bidang_id'  => $request->role === 'manajemen'
                ? $bidang->id
                : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
