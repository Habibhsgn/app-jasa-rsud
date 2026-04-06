<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;

class masterRuanganControllers extends Controller
{
    public function index()
    {
        $ruangan = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        // Hitung total persen saat ini
        $totalPersen = $ruangan->sum('persen_default');

        return view('pages.masterRuangan', compact('ruangan', 'totalPersen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:255'
        ]);

        Ruangan::create([
            'nama_ruangan' => strtoupper($request->nama_ruangan),
            'persen_default' => 0 // Default 0 saat baru ditambah
        ]);

        return back()->with('success', 'Ruangan baru berhasil ditambahkan.');
    }

    public function updateBulk(Request $request)
    {
        // Pastikan input array ada
        if (!$request->has('ruangan_id')) {
            return back()->with('error', 'Tidak ada data ruangan.');
        }

        $totalPersen = array_sum($request->persen_default);

        // Validasi ketat harus 100%
        if (round($totalPersen, 2) != 100.00) {
            return back()->with('error', 'Gagal menyimpan! Total seluruh persentase harus tepat 100%. Saat ini: ' . round($totalPersen, 2) . '%');
        }

        // Looping update semua ruangan
        foreach ($request->ruangan_id as $index => $id) {
            Ruangan::where('id', $id)->update([
                'nama_ruangan' => strtoupper($request->nama_ruangan[$index]),
                'persen_default' => $request->persen_default[$index] ?? 0
            ]);
        }

        return back()->with('success', 'Master Ruangan dan Persentase berhasil diperbarui secara massal!');
    }

    public function destroy($id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $ruangan->delete();

        return back()->with('success', 'Ruangan berhasil dihapus.');
    }
}