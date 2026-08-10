<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterBidang;

class MasterBidangController extends Controller
{
    public function index()
    {
        $bidang = MasterBidang::orderBy('nama_bidang')->get();

        return view('pages.master_bidang', compact('bidang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_bidang' => 'required|string|max:50|unique:master_bidang,kode_bidang',
            'nama_bidang' => 'required|string|max:150',
        ]);

        MasterBidang::create([
            'kode_bidang' => strtoupper($request->kode_bidang),
            'nama_bidang' => strtoupper($request->nama_bidang),
            'is_active' => true,
        ]);

        return back()->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_bidang' => 'required|string|max:50|unique:master_bidang,kode_bidang,' . $id,
            'nama_bidang' => 'required|string|max:150',
        ]);

        $bidang = MasterBidang::findOrFail($id);

        $bidang->update([
            'kode_bidang' => strtoupper($request->kode_bidang),
            'nama_bidang' => strtoupper($request->nama_bidang),
        ]);

        return back()->with('success', 'Bidang berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $bidang = MasterBidang::findOrFail($id);

        $bidang->is_active = !$bidang->is_active;
        $bidang->save();

        return back()->with('success', 'Status bidang berhasil diperbarui.');
    }
}
