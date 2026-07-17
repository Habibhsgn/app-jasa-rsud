@extends('layouts.app')

@section('title', 'Detail Review — ' . $periodeInfo->periode_label)

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0"><strong>Review Periode: {{ $periodeInfo->periode_label }}</strong></h1>
        <a href="{{ route('management.index.scoring.index') }}" class="btn btn-outline-secondary btn-sm">
            <i data-feather="arrow-left" class="me-1"></i>
            Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3">
        @if ($periodeInfo->status == 'submit')
            <span class="badge bg-success">Menunggu Review</span>
        @elseif($periodeInfo->status == 'selesai')
            <span class="badge bg-primary">Selesai</span>
        @elseif($periodeInfo->status == 'revisi')
            <span class="badge bg-danger">Revisi</span>
        @endif
    </div>

    @if ($periodeInfo->status == 'revisi' && $periodeInfo->catatan_revisi)
        <div class="alert alert-warning">
            <strong>Catatan Revisi Sebelumnya:</strong><br>
            {{ $periodeInfo->catatan_revisi }}
        </div>
    @endif

    @foreach ($ruangans as $item)
        <div class="card mb-4">
            <div class="card-header bg-transparent border-bottom">
                <strong>{{ $item->nama_ruangan }}</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NO</th>
                                <th>NAMA</th>
                                <th>NIP/NIP3K</th>
                                <th>Jabatan</th>
                                <th>Pend. Formal</th>
                                <th>Pend. Non Formal</th>
                                <th>Gaji Pokok</th>
                                <th>Risk</th>
                                <th>Emergency</th>
                                <th>Cuti</th>
                                <th>Izin</th>
                                <th>Tanpa Izin</th>
                                <th>Telat</th>
                                <th>Sikap</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Jumlah Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->pegawai as $i => $p)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-start">{{ $p->nama }}</td>
                                    <td>{{ $p->id_petugas }}</td>
                                    <td>{{ $p->jabatan }}</td>
                                    <td>{{ $p->pendidikan_formal }}</td>
                                    <td>{{ $p->pendidikan_non_formal }}</td>
                                    <td>{{ $p->gaji_pokok }}</td>
                                    <td>{{ $p->risk }}</td>
                                    <td>{{ $p->emergency }}</td>
                                    <td>{{ $p->cuti }}</td>
                                    <td>{{ $p->izin }}</td>
                                    <td>{{ $p->tanpa_izin }}</td>
                                    <td>{{ $p->telat }}</td>
                                    <td>{{ $p->sikap }}</td>
                                    <td class="fw-bold">{{ $p->jumlah }}</td>
                                    <td class="text-start">{{ $p->keterangan }}</td>
                                    <td class="fw-bold text-primary">{{ $p->jumlah_akhir }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    @if ($periodeInfo->bisa_direview)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Keputusan Review</h5>

                <div class="d-flex gap-2">
                    {{-- Setujui --}}
                    <form action="{{ route('management.index.scoring.approve', $periodeInfo->periode) }}" method="POST"
                        onsubmit="return confirm('Setujui pengajuan periode {{ $periodeInfo->periode_label }}? Data akan dikunci permanen dan tidak bisa direvisi lagi kecuali dikembalikan status revisi.');">
                        @csrf
                        <button type="submit" class="btn btn-success px-4">
                            <i data-feather="check-circle" class="me-1"></i>
                            Setujui
                        </button>
                    </form>

                    {{-- Minta Revisi --}}
                    <button type="button" class="btn btn-danger px-4" data-bs-toggle="modal" data-bs-target="#modalRevisi">
                        <i data-feather="rotate-ccw" class="me-1"></i>
                        Minta Revisi
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Catatan Revisi --}}
        <div class="modal fade" id="modalRevisi" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('management.index.scoring.revisi', $periodeInfo->periode) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Catatan Revisi — {{ $periodeInfo->periode_label }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label fw-bold">Jelaskan bagian yang perlu diperbaiki</label>
                            <textarea name="catatan_revisi" class="form-control" rows="4" required
                                placeholder="Contoh: Data cuti pegawai A belum sesuai, mohon dicek ulang."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Kirim Revisi</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection