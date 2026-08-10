<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MasterBidang;
use App\Models\Role;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $ruanganTerpakai = User::whereNotNull('ruangan_id')
            ->pluck('ruangan_id');

        $ruanganTersedia = Ruangan::whereNotIn(
            'id',
            $ruanganTerpakai
        )
            ->orderBy('nama_ruangan')
            ->get();

        return view(
            'auth.register',
            compact('ruanganTersedia')
        );
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:' . User::class,
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],

            'role' => [
                'required',
                'in:karu,manajemen',
            ],

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

        /*
        |--------------------------------------------------------------------------
        | Cari Role
        |--------------------------------------------------------------------------
        */

        $role = Role::where('code', $request->role)
            ->first();

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'Role tidak valid.',
            ]);
        }

        $ruanganId = null;
        $bidangId = null;

        /*
        |--------------------------------------------------------------------------
        | Registrasi Karu
        |--------------------------------------------------------------------------
        */

        if ($request->role === 'karu') {

            $sudahDipakai = User::where(
                'ruangan_id',
                $request->ruangan_id
            )->exists();

            if ($sudahDipakai) {
                return back()
                    ->withErrors([
                        'ruangan_id' =>
                        'Ruangan tersebut sudah memiliki Kepala Ruangan.',
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

            $bidang = MasterBidang::whereRaw(
                'UPPER(kode_bidang) = ?',
                [
                    strtoupper($request->kode_bidang),
                ]
            )
                ->where('is_active', true)
                ->first();

            if (!$bidang) {
                return back()
                    ->withErrors([
                        'kode_bidang' =>
                        'Kode bidang tidak valid.',
                    ])
                    ->withInput();
            }

            if (
                User::where(
                    'bidang_id',
                    $bidang->id
                )->exists()
            ) {
                return back()
                    ->withErrors([
                        'kode_bidang' =>
                        'Bidang tersebut sudah memiliki akun manajemen.',
                    ])
                    ->withInput();
            }

            $bidangId = $bidang->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        $user = User::create([
            'name' => $request->name,

            'email' => $request->email,

            'password' => $request->password,

            'role_id' => $role->id,

            'ruangan_id' => $ruanganId,

            'bidang_id' => $bidangId,

            'is_active' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Kirim Email Verification
        |--------------------------------------------------------------------------
        */

        event(new Registered($user));

        /*
        |--------------------------------------------------------------------------
        | Jangan Auto Login
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Registrasi berhasil. Silakan cek email Anda untuk melakukan verifikasi.'
            );
    }
}
