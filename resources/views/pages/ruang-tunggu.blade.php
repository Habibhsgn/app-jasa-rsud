@extends('layouts.app')

@section('title', 'Ruang Tunggu Pindah Pegawai')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <strong>🚪 Ruang Tunggu — Pengajuan Pindah Pegawai</strong>
        </h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (auth()->user()->role !== 'admin')
        <div class="alert alert-info">
            Menampilkan pegawai yang diajukan pindah <strong>ke ruangan Anda</strong>.
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th>Nama Pegawai</th>
                            <th>Ruangan Asal</th>
                            <th>Ruangan Tujuan</th>
                            <th>Diajukan Oleh</th>
                            <th>Diajukan Pada</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pegawaiTransit as $index => $p)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="fw-bold">{{ $p->nama }}</td>
                                <td>{{ $p->ruangan->nama_ruangan ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        {{ $p->ruanganTujuan->nama_ruangan ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $p->diajukanOleh->name ?? '-' }}</td>
                                <td>{{ $p->diajukan_at ? $p->diajukan_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="text-center">

                                    @php
                                        $bolehProses =
                                            auth()->user()->role === 'admin' ||
                                            (in_array(auth()->user()->role, ['karu', 'koordinator_karu']) &&
                                                auth()->user()->ruangan_id == $p->ruangan_tujuan_id);
                                    @endphp

                                    @if ($bolehProses)
                                        <form action="{{ route('ruang-tunggu.terima', $p->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm"
                                                onclick="return confirm('Terima pegawai ini masuk ke ruangan Anda?')">
                                                ✅ Terima
                                            </button>
                                        </form>

                                        <form action="{{ route('ruang-tunggu.tolak', $p->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Tolak pengajuan pindah pegawai ini?')">
                                                ❌ Tolak
                                            </button>
                                        </form>
                                    @endif

                                    @if (auth()->user()->role === 'admin')
                                        <form action="{{ route('pegawai.pindah.batal', $p->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                onclick="return confirm('Batalkan pengajuan pindah ini? Pegawai akan kembali aktif tanpa pindah ruangan.')">
                                                ↩️ Batalkan
                                            </button>
                                        </form>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada pegawai yang sedang menunggu proses pindah.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
