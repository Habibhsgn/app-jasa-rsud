<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruangan;

class masterRuanganControllers extends Controller
{
    public function index()
    {
        $ruangan = Ruangan::orderByDesc('is_active')
            ->orderBy('nama_ruangan')
            ->get();

        $totalPersen = $ruangan
            ->where('is_active', true)
            ->sum('persen_default');

        return view('pages.masterRuangan', compact(
            'ruangan',
            'totalPersen'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100',
            'resiko'       => 'required|numeric|in:1,2,4,6',
            'emergency'    => 'required|numeric|in:1,2,4,6',
        ]);

        Ruangan::create([
            'nama_ruangan'   => strtoupper($request->nama_ruangan),
            'persen_default' => 0,
            'resiko'         => $request->resiko,
            'emergency'      => $request->emergency,
            'is_active'      => true,
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

            Ruangan::where('id', $id)->update([
                'nama_ruangan'   => strtoupper($request->nama_ruangan[$index]),
                'persen_default' => $request->persen_default[$index],
                'resiko'         => $request->resiko[$index],
                'emergency'      => $request->emergency[$index],
            ]);
        }

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