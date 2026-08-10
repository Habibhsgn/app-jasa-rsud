<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
        <strong>{{ $item->nama_ruangan }}</strong>

        <div>
            @if ($item->status == 'submit')
                <span class="badge bg-success">Menunggu Review</span>
            @elseif($item->status == 'selesai')
                <span class="badge bg-primary">Selesai</span>
            @elseif($item->status == 'revisi')
                <span class="badge bg-danger">Revisi</span>
            @elseif($item->status == 'draft')
                <span class="badge bg-warning">Draft (Belum Submit)</span>
            @endif
        </div>
    </div>

    <div class="card-body">
        @if ($item->status == 'revisi' && $item->catatan_revisi)
            <div class="alert alert-warning">
                <strong>Catatan Revisi Sebelumnya:</strong><br>
                {{ $item->catatan_revisi }}
            </div>
        @endif

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

        {{-- Keputusan review: HANYA untuk ruangan ini --}}
        @if ($item->bisa_direview)
            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                {{-- Setujui ruangan ini saja --}}
                <form action="{{ route('management.index.scoring.approve', [$periodeInfo->periode, $item->id, 'pegawai']) }}"
                    method="POST"
                    onsubmit="return confirm('Setujui pengajuan {{ $item->nama_ruangan }}? Data akan dikunci permanen.');">
                    @csrf
                    <button type="submit" class="btn btn-success px-4">
                        <i data-feather="check-circle" class="me-1"></i>
                        Setujui
                    </button>
                </form>

                {{-- Minta revisi ruangan ini saja --}}
                <button type="button" class="btn btn-danger px-4" data-bs-toggle="modal"
                    data-bs-target="#modalRevisi{{ $item->id }}">
                    <i data-feather="rotate-ccw" class="me-1"></i>
                    Minta Revisi
                </button>
            </div>

            {{-- Modal Catatan Revisi khusus ruangan ini --}}
            <div class="modal fade" id="modalRevisi{{ $item->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form
                        action="{{ route('management.index.scoring.revisi', [$periodeInfo->periode, $item->id, 'pegawai']) }}"
                        method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Catatan Revisi — {{ $item->nama_ruangan }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label fw-bold">Jelaskan bagian yang perlu diperbaiki</label>
                                <textarea name="catatan_revisi" class="form-control" rows="4" required
                                    placeholder="Contoh: Data cuti pegawai A belum sesuai, mohon dicek ulang."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger">Kirim Revisi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>