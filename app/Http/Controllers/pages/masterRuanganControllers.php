<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;
use App\Models\MasterBidang;

class masterRuanganControllers extends Controller
{
    public function index()
    {
        $ruangan = Ruangan::with('bidang')
            ->orderByDesc('is_active')
            ->orderBy('nama_ruangan')
            ->get();

        $bidang = MasterBidang::where('is_active', true)
            ->orderBy('nama_bidang')
            ->get();

        $totalPersen = $ruangan
            ->where('is_active', true)
            ->sum('persen_default');


        return view('pages.masterRuangan', compact(
            'ruangan',
            'bidang',
            'totalPersen'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'bidang_id' => 'required|exists:master_bidang,id',
            'resiko'       => 'required|numeric|in:1,2,4,6',
            'emergency'    => 'required|numeric|in:1,2,4,6',
        ]);

        Ruangan::create([
            'nama_ruangan' => strtoupper($request->nama_ruangan),
            'bidang_id' => $request->bidang_id,
            'persen_default' => 0,
            'resiko' => $request->resiko,
            'emergency' => $request->emergency,
            'is_active' => true,
        ]);

        return back()->with(
            'success',
            'Ruangan berhasil ditambahkan.'
        );
    }

    public function updateBulk(Request $request)
    {
        $request->validate([
            'ruangan_id'        => 'required|array',
            'nama_ruangan.*'    => 'required|string|max:100',
            'persen_default.*'  => 'required|numeric|min:0|max:100',
            'resiko.*'          => 'required|numeric|in:1,2,4,6',
            'emergency.*'       => 'required|numeric|in:1,2,4,6',
            'bidang_id.*' => 'required|exists:master_bidang,id',
        ]);

        $totalPersen = collect($request->persen_default)->sum();

        if (round($totalPersen, 2) != 100) {
            return back()->with(
                'error',
                'Total seluruh persentase harus tepat 100%. Saat ini: '
                    . round($totalPersen, 2) . '%'
            );
        }

        foreach ($request->ruangan_id as $index => $id) {

            $ruangan = Ruangan::findOrFail($id);

            $ruangan->nama_ruangan = strtoupper($request->nama_ruangan[$index]);
            $ruangan->bidang_id = $request->bidang_id[$index];
            $ruangan->persen_default = $request->persen_default[$index];
            $ruangan->resiko = $request->resiko[$index];
            $ruangan->emergency = $request->emergency[$index];

            $ruangan->save();

            // dd($ruangan->fresh()->toArray());
        }

        // dd('Tidak ada bidang yang berubah.');

        return back()->with(
            'success',
            'Data ruangan berhasil diperbarui.'
        );
    }

    public function toggleStatus($id)
    {
        $ruangan = Ruangan::findOrFail($id);

        $ruangan->update([
            'is_active' => !$ruangan->is_active
        ]);

        return back()->with(
            'success',
            'Status ruangan berhasil diperbarui.'
        );
    }
}
