<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PegawaiImport;
use Illuminate\Validation\Rule;

class pegawaiControllers extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $ruanganQuery = Ruangan::select('id', 'nama_ruangan', 'resiko', 'emergency')
            ->orderBy('nama_ruangan', 'asc');

        $pegawaiQuery = Pegawai::with('ruangan:id,nama_ruangan,resiko,emergency')
            ->orderBy('nama', 'asc');

        // KARU + KOORDINATOR KARU -> hanya ruangan sendiri
        if (in_array($user->role, ['karu', 'koordinator_karu'])) {
            $ruanganQuery->where('id', $user->ruangan_id);
            $pegawaiQuery->where('ruangan_id', $user->ruangan_id);
        }

        $ruangan = $ruanganQuery->get();

        $pegawaiGrouped = $pegawaiQuery->get()
            ->groupBy(fn($item) => $item->ruangan_id ?? 0)
            ->sortBy(function ($group, $key) use ($ruangan) {
                $r = $ruangan->firstWhere('id', $key);
                return $r->nama_ruangan ?? 'ZZZ_TANPA_RUANGAN';
            });

        return view('pages.pegawai', compact('pegawaiGrouped', 'ruangan'));
    }

    public function store(Request $request)
    {
        if (in_array(Auth::user()->role, ['karu', 'koordinator_karu'])) {
            abort(403, 'Tidak boleh edit data pegawai');
        }

        $request->validate([
            'nama' => 'required',
            'id_petugas' => 'nullable',
            'jabatan' => 'required',
            'ruangan_id' => 'required|exists:ruangan,id',
            'pendidikan_non_formal' => 'required|string',
            'gaji_pokok' => 'required|numeric',
        ]);

        Pegawai::create($request->all());

        return back()->with('success', 'Pegawai berhasil ditambahkan.');
    }

    /**
     * Import Excel
     */
    public function import(Request $request)
    {
        if (in_array(Auth::user()->role, ['karu', 'koordinator_karu'])) {
            abort(403, 'Tidak boleh import data pegawai');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ], [
            'file.required' => 'Silakan pilih file Excel.',
            'file.mimes' => 'File harus berformat xlsx, xls, atau csv.'
        ]);

        $import = new PegawaiImport();

        Excel::import($import, $request->file('file'));

        // Ada error validasi
        if (!$import->passed()) {

            return back()
                ->withInput()
                ->with('import_errors', $import->errors);
        }

        // Tidak ada data valid
        if (count($import->pegawai) === 0) {
            return back()->with(
                'error',
                'Tidak ada data pegawai yang dapat diimport.'
            );
        }

        DB::transaction(function () use ($import) {

            // Hapus seluruh data lama
            Pegawai::query()->delete();

            // Insert data baru
            Pegawai::insert($import->pegawai);
        });

        return back()->with(
            'success',
            'Import berhasil. Total ' .
                count($import->pegawai) .
                ' data pegawai berhasil diperbarui.'
        );
    }

    public function update(Request $request, $id)
    {
        if (in_array(Auth::user()->role, ['karu', 'koordinator_karu'])) {
            abort(403, 'Tidak boleh edit data pegawai');
        }

        $request->validate([
            'nama' => 'required',
            'id_petugas' => 'nullable',
            'jabatan' => 'required',
            'ruangan_id' => 'required|exists:ruangan,id',
            'pendidikan_non_formal' => 'required|string',
            'gaji_pokok' => 'required|numeric',
        ]);

        $pegawai = Pegawai::findOrFail($id);
        $pegawai->update($request->all());

        return back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function updateRuanganResikoEmergency(Request $request, $id)
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Hanya admin yang boleh mengubah resiko/emergency.');

        $request->validate([
            'resiko' => 'required|in:1,2,4,6',
            'emergency' => 'required|in:1,2,4,6',
        ]);

        $ruangan = Ruangan::findOrFail($id);

        $ruangan->update([
            'resiko' => $request->resiko,
            'emergency' => $request->emergency,
        ]);

        return response()->json([
            'success' => true,
            'resiko' => $ruangan->resiko,
            'emergency' => $ruangan->emergency,
        ]);
    }

    public function updateIdPetugas(Request $request, $id)
    {
        abort_unless(
            in_array(Auth::user()->role, ['admin', 'karu', 'koordinator_karu']),
            403
        );

        $request->validate([
            'id_petugas' => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('pegawai', 'id_petugas')->ignore($id),
            ],
        ]);

        $pegawai = Pegawai::findOrFail($id);

        $pegawai->update([
            'id_petugas' => $request->id_petugas
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function destroy($id)
    {
        if (in_array(Auth::user()->role, ['karu', 'koordinator_karu'])) {
            abort(403, 'Tidak boleh hapus data pegawai');
        }

        Pegawai::findOrFail($id)->delete();

        return back()->with('success', 'Data pegawai berhasil dihapus.');
    }
}
