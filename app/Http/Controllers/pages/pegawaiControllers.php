<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\Ruangan;
use Illuminate\Support\Facades\Auth;

class pegawaiControllers extends Controller
{
    public function index()
    {
        // 1. Ambil data ruangan untuk Dropdown di Modal
        $ruanganQuery = Ruangan::orderBy('nama_ruangan', 'asc');

        // 2. Query dasar Pegawai
        $pegawaiQuery = Pegawai::with('ruangan')->orderBy('nama', 'asc');

        // --- LOGIKA FILTER BERDASARKAN ROLE ---
        if (Auth::user()->role === 'karu') {
            $userRuanganId = Auth::user()->ruangan_id;

            // KARU hanya bisa melihat ruangan miliknya di dropdown
            $ruanganQuery->where('id', $userRuanganId);

            // KARU hanya bisa melihat daftar pegawai di ruangannya saja
            $pegawaiQuery->where('ruangan_id', $userRuanganId);
        }

        // 3. Eksekusi Query
        $ruangan = $ruanganQuery->get();

        $pegawaiGrouped = $pegawaiQuery->get()
            ->groupBy(function ($item) {
                return $item->ruangan ? $item->ruangan->nama_ruangan : 'TANPA RUANGAN';
            })
            ->sortKeys();

        return view('pages.pegawai', compact('pegawaiGrouped', 'ruangan'));
    }
    public function store(Request $request)
    {
        // Jika dia KARU, paksa ruangan_id menggunakan id ruangan dia sendiri
        if (Auth::user()->role === 'karu') {
            $request->merge(['ruangan_id' => Auth::user()->ruangan_id]);
        }

        $request->validate([
            'nama' => 'required',
            'id_petugas' => 'required',
            'jabatan' => 'required',
            'ruangan_id' => 'required|exists:ruangan,id'
        ]);

        Pegawai::create($request->all());
        return back()->with('success', 'Pegawai berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'id_petugas' => 'required|string|max:100',
            'jabatan' => 'required|string|max:255',
            'ruangan_id' => 'required'
        ]);

        $pegawai = Pegawai::findOrFail($id);
        $pegawai->update($request->all());

        return back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        $pegawai->delete();

        return back()->with('success', 'Data pegawai berhasil dihapus.');
    }
}
