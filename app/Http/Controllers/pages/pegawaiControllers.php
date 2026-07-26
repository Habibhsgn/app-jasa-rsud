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
use App\Exports\PegawaiExport;


class pegawaiControllers extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Untuk dropdown (selalu semua ruangan)
        $ruanganDropdown = Ruangan::select('id', 'nama_ruangan', 'resiko', 'emergency')
            ->orderBy('nama_ruangan')
            ->get();

        // Untuk tabel
        $ruanganQuery = Ruangan::select('id', 'nama_ruangan', 'resiko', 'emergency')
            ->orderBy('nama_ruangan');

        $pegawaiQuery = Pegawai::with('ruangan:id,nama_ruangan,resiko,emergency')
            ->orderBy('nama');

        if (in_array($user->role?->code, ['karu', 'koordinator_karu'])) {
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

        return view('pages.pegawai', compact(
            'pegawaiGrouped',
            'ruangan',
            'ruanganDropdown'
        ));
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
            'pendidikan_non_formal' => 'nullable',
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

        // Tidak ada data valid sama sekali
        if (count($import->toUpdate) === 0 && count($import->toInsert) === 0) {
            return back()->with(
                'error',
                'Tidak ada data pegawai yang dapat diimport.'
            );
        }

        DB::transaction(function () use ($import) {

            // UPDATE pegawai yang sudah ada (id tetap sama -> relasi JasaPegawai aman)
            foreach ($import->toUpdate as $row) {
                Pegawai::where('id', $row['id'])->update($row['data']);
            }

            // INSERT pegawai baru
            if (count($import->toInsert) > 0) {
                Pegawai::insert($import->toInsert);
            }
        });

        $pesan = sprintf(
            'Import berhasil. %d data diperbarui, %d data baru ditambahkan.',
            count($import->toUpdate),
            count($import->toInsert)
        );

        // Kalau ada baris yang di-skip karena bentrok id_petugas nonaktif, dsb,
        // errors tetap kosong di sini karena passed() sudah true -> tidak ada errors.
        // (baris dengan error lain sudah difilter di dalam PegawaiImport)

        return back()->with('success', $pesan);
    }

    public function export()
    {
        abort_unless(
            in_array(Auth::user()->role, ['admin', 'karu', 'koordinator_karu']),
            403
        );

        $filename = 'data-pegawai-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new PegawaiExport(), $filename);
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
            'pendidikan_non_formal' => 'nullable',
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

    /**
     * ADMIN atau KARU/KOORDINATOR RUANGAN ASAL mengajukan pindah.
     */
    public function ajukanPindah(Request $request, $id)
    {
        $user = Auth::user();
        $pegawai = Pegawai::findOrFail($id);

        // ==========================
        // OTORISASI
        // ==========================
        $isAdmin = $user->role?->code === 'admin';
        $isKaruRuanganAsal = in_array($user->role?->code, ['karu', 'koordinator_karu'])
            && $user->ruangan_id == $pegawai->ruangan_id;

        if (!$isAdmin && !$isKaruRuanganAsal) {
            abort(403, 'Anda tidak berwenang mengajukan pindah untuk pegawai ini.');
        }

        // ==========================
        // CEGAH DOBEL PENGAJUAN
        // ==========================
        if ($pegawai->status === 'pindah') {
            return back()->with(
                'error',
                'Pegawai ini sudah dalam proses pindah. Hubungi admin untuk membatalkan pengajuan sebelumnya jika ingin mengubah tujuan.'
            );
        }

        $request->validate([
            'ruangan_tujuan_id' => 'required|exists:ruangan,id',
        ]);

        if ((int) $request->ruangan_tujuan_id === (int) $pegawai->ruangan_id) {
            return back()->with('error', 'Ruangan tujuan tidak boleh sama dengan ruangan saat ini.');
        }

        $pegawai->update([
            'status' => 'pindah',
            'ruangan_tujuan_id' => $request->ruangan_tujuan_id,
            'diajukan_oleh' => $user->id,
            'diajukan_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan pindah berhasil dibuat. Menunggu konfirmasi ruangan tujuan.');
    }

    /**
     * ADMIN membatalkan pengajuan pindah (misal salah input ruangan tujuan).
     * Mengembalikan pegawai ke status aktif tanpa lewat approval ruangan tujuan.
     */
    public function batalkanPindah($id)
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Hanya admin yang boleh membatalkan pengajuan pindah.');

        $pegawai = Pegawai::findOrFail($id);

        if ($pegawai->status !== 'pindah') {
            return back()->with('error', 'Pegawai ini tidak sedang dalam proses pindah.');
        }

        $pegawai->update([
            'status' => 'aktif',
            'ruangan_tujuan_id' => null,
            'diajukan_oleh' => null,
            'diajukan_at' => null,
        ]);

        return back()->with('success', 'Pengajuan pindah berhasil dibatalkan.');
    }

    /**
     * Halaman Ruang Tunggu.
     * ADMIN -> lihat semua pegawai yang sedang transit, dikelompokkan per ruangan tujuan.
     * KARU/KOORDINATOR -> hanya lihat pegawai yang ditujukan ke ruangannya sendiri.
     */
    public function ruangTunggu()
    {
        $user = Auth::user();

        $query = Pegawai::with([
            'ruangan:id,nama_ruangan',
            'ruanganTujuan:id,nama_ruangan',
            'diajukanOleh:id,name',
        ])
            ->where('status', 'pindah')
            ->orderBy('diajukan_at', 'desc');

        if (in_array($user->role?->code, ['karu', 'koordinator_karu'])) {
            $query->where('ruangan_tujuan_id', $user->ruangan_id);
        }

        $pegawaiTransit = $query->get();

        return view('pages.ruang-tunggu', compact('pegawaiTransit'));
    }

    /**
     * ADMIN atau KARU/KOORDINATOR RUANGAN TUJUAN menerima pegawai pindah.
     */
    public function terimaPindah($id)
    {
        $user = Auth::user();
        $pegawai = Pegawai::findOrFail($id);

        if ($pegawai->status !== 'pindah') {
            return back()->with('error', 'Pegawai ini tidak sedang dalam proses pindah.');
        }

        $isAdmin = $user->role?->code === 'admin';
        $isKaruRuanganTujuan = in_array($user->role?->code, ['karu', 'koordinator_karu'])
            && $user->ruangan_id == $pegawai->ruangan_tujuan_id;

        if (!$isAdmin && !$isKaruRuanganTujuan) {
            abort(403, 'Anda tidak berwenang menerima pegawai ini.');
        }

        $ruanganBaru = Ruangan::findOrFail($pegawai->ruangan_tujuan_id);

        DB::transaction(function () use ($pegawai, $ruanganBaru) {
            $pegawai->update([
                'ruangan_id' => $ruanganBaru->id,
                // risk/emergency ikut disesuaikan ke ruangan baru, konsisten dengan logic import
                'risk' => $ruanganBaru->resiko,
                'emergency' => $ruanganBaru->emergency,
                'status' => 'aktif',
                'ruangan_tujuan_id' => null,
                'diajukan_oleh' => null,
                'diajukan_at' => null,
            ]);
        });

        return back()->with('success', "Pegawai '{$pegawai->nama}' berhasil diterima di ruangan {$ruanganBaru->nama_ruangan}.");
    }

    /**
     * ADMIN atau KARU/KOORDINATOR RUANGAN TUJUAN menolak pengajuan pindah.
     * Pegawai kembali aktif di ruangan asalnya (ruangan_id tidak pernah berubah selama transit).
     */
    public function tolakPindah($id)
    {
        $user = Auth::user();
        $pegawai = Pegawai::findOrFail($id);

        if ($pegawai->status !== 'pindah') {
            return back()->with('error', 'Pegawai ini tidak sedang dalam proses pindah.');
        }

        $isAdmin = $user->role?->code === 'admin';
        $isKaruRuanganTujuan = in_array($user->role?->code, ['karu', 'koordinator_karu'])
            && $user->ruangan_id == $pegawai->ruangan_tujuan_id;

        if (!$isAdmin && !$isKaruRuanganTujuan) {
            abort(403, 'Anda tidak berwenang menolak pengajuan pindah pegawai ini.');
        }

        $pegawai->update([
            'status' => 'aktif',
            'ruangan_tujuan_id' => null,
            'diajukan_oleh' => null,
            'diajukan_at' => null,
        ]);

        return back()->with('success', "Pengajuan pindah untuk '{$pegawai->nama}' ditolak. Pegawai tetap di ruangan asal.");
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
