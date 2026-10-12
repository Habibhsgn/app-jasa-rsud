{{-- Tampilan detail tab "Semua". Dipanggil dari show.blade.php (memakai variabel milik show). --}}
<div class="row">
    {{-- Status & Aksi --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <strong>Status:</strong>
                    @if ($c->status == 'disetujui')
                        <span class="badge bg-success ms-2"><i data-feather="circle-check" class="me-1"></i>Disetujui</span>
                    @else
                        <span class="badge bg-warning ms-2"><i data-feather="clock" class="me-1"></i>Pending</span>
                    @endif

                    @if ($c->tgl_verifikasi)
                        <span class="ms-3 text-muted small">
                            <i data-feather="calendar" class="me-1"></i>Verifikasi: {{ $c->tgl_verifikasi->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if (!$canAll)
                        {{-- hanya yang berhak melihat semua data boleh ubah status --}}
                    @elseif ($c->status == 'pending')
                        <form action="{{ route('inacbg.update-status', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui klaim ini?')">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="disetujui">
                            <button type="submit" class="btn btn-success btn-sm"><i data-feather="check" class="me-1"></i>Setujui</button>
                        </form>
                    @else
                        <form action="{{ route('inacbg.update-status', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Kembalikan ke pending?')">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="pending">
                            <button type="submit" class="btn btn-warning btn-sm"><i data-feather="rotate-clockwise-2" class="me-1"></i>Pending</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Keterangan semua seksi --}}
    @if (count($sections))
        <div class="col-12 mb-4">
            <div class="row g-3">
                @foreach ($sections as $key => $s)
                    @php $meta = \App\Models\InacbgClaim::SECTIONS[$key]; @endphp
                    <div class="col-lg-4 col-md-6">
                        <div class="card border-0 border-top border-3 border-{{ $meta['color'] }} shadow-sm h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i data-feather="{{ $meta['icon'] }}" class="me-2 text-{{ $meta['color'] }}"></i>{{ $s['label'] }}</h6>
                                @if ($s['data'])
                                    <span class="badge bg-success">Terisi</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum diisi</span>
                                @endif
                            </div>
                            <div class="card-body">
                                @if ($s['data'])
                                    <div class="p-3 bg-light rounded">{!! nl2br(e($s['data']['teks'])) !!}</div>
                                    <div class="text-muted small mt-2">
                                        Diisi oleh <strong>{{ $s['data']['user_name'] ?? '-' }}</strong>
                                        @if (!empty($s['data']['updated_at']))
                                            &middot; {{ \Carbon\Carbon::parse($s['data']['updated_at'])->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                @else
                                    <div class="text-muted small">Belum ada keterangan.</div>
                                @endif

                                @if ($s['canEdit'])
                                    <form action="{{ route('inacbg.update-keterangan', [$c, $key]) }}" method="POST" class="mt-3">
                                        @csrf @method('PUT')
                                        <textarea name="teks" rows="3" maxlength="2000" class="form-control mb-2" required
                                            placeholder="Tulis keterangan {{ $s['label'] }}...">{{ $s['data']['teks'] ?? '' }}</textarea>
                                        <button type="submit" class="btn btn-sm btn-{{ $meta['color'] }}">Simpan</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Perhitungan Jasa Staff --}}
    @if ($canAll && $jasaData)
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i data-feather="chart-pie" class="me-2 text-primary"></i>Perhitungan Jasa Staff</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Total Jasa:</strong> Rp {{ number_format($jasaData['totalJasa'], 2) }} |
                        <strong>Jasa Staff ({{ $jasaData['persenStaff'] }}%):</strong> Rp {{ number_format($jasaData['jasaStaff'], 2) }}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Persen</th>
                                    <th class="text-end">Jumlah Jasa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jasaData['breakdown'] as $item)
                                    <tr>
                                        <td>{{ $item['label'] }}</td>
                                        <td>{{ number_format($item['persen'], 2) }}%</td>
                                        <td class="text-end">Rp {{ number_format($item['jumlah'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td class="fw-bold">Total</td>
                                    <td>100%</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($jasaData['grandTotalStaff'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Kolom kiri: pasien & demografi --}}
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="user" class="me-2 text-primary"></i>Informasi Pasien</h6>
            </div>
            <div class="card-body">
                @foreach ($pasien as $label => $val)
                    <div class="row mb-2">
                        <div class="col-4"><div class="text-muted small">{{ $label }}</div></div>
                        <div class="col-8"><div class="fw-semibold">{{ $val ?: '-' }}</div></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="calendar" class="me-2 text-primary"></i>Demografi & Rawat</h6>
            </div>
            <div class="card-body">
                @foreach ($demografi as $label => $val)
                    <div class="row mb-2">
                        <div class="col-4"><div class="text-muted small">{{ $label }}</div></div>
                        <div class="col-8"><div class="fw-semibold">{{ $val ?: '-' }}</div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Kolom kanan: INA-CBG, tarif, verifikasi --}}
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="code" class="me-2 text-primary"></i>INA-CBG Grouper</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-4"><div class="text-muted small">Kode INA-CBG</div></div>
                    <div class="col-8"><div class="fw-semibold"><code>{{ $c->inacbg ?: '-' }}</code></div></div>
                </div>
                @foreach ([
                    'Deskripsi' => $c->deskripsi_inacbg,
                    'Subacute' => $c->subacute,
                    'Chronic' => $c->chronic,
                    'Versi INA-CBG' => $c->versi_inacbg,
                    'Versi Grouper' => $c->versi_grouper,
                ] as $label => $val)
                    <div class="row mb-2">
                        <div class="col-4"><div class="text-muted small">{{ $label }}</div></div>
                        <div class="col-8"><div class="fw-semibold">{{ $val ?: '-' }}</div></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="coin" class="me-2 text-primary"></i>Tarif</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td>Tarif INA-CBG</td><td class="text-end">{{ $rp($c->tarif_inacbg) }}</td></tr>
                            <tr><td>Tarif Subacute</td><td class="text-end">{{ $rp($c->tarif_subacute) }}</td></tr>
                            <tr><td>Tarif Chronic</td><td class="text-end">{{ $rp($c->tarif_chronic) }}</td></tr>
                            <tr><td>SP</td><td class="text-end">{{ $rp($c->tarif_sp) }}</td></tr>
                            <tr><td>SR</td><td class="text-end">{{ $rp($c->tarif_sr) }}</td></tr>
                            <tr><td>SI</td><td class="text-end">{{ $rp($c->tarif_si) }}</td></tr>
                            <tr><td>SD</td><td class="text-end">{{ $rp($c->tarif_sd) }}</td></tr>
                            <tr class="border-top"><td class="fw-bold">Total Tarif</td><td class="text-end fw-bold">{{ $rp($c->total_tarif) }}</td></tr>
                            <tr><td class="fw-bold">Tarif RS</td><td class="text-end fw-bold">{{ $rp($c->tarif_rs) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="circle-check" class="me-2 text-primary"></i>Verifikasi & Klaim</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td>Tgl. Verifikasi</td><td class="text-end">{{ $c->tgl_verifikasi ? $c->tgl_verifikasi->format('d/m/Y') : '-' }}</td></tr>
                            <tr><td>Biaya Riil RS</td><td class="text-end">{{ $rp($c->biaya_riil_rs) }}</td></tr>
                            <tr><td>Biaya Diajukan</td><td class="text-end">{{ $rp($c->biaya_diajukan) }}</td></tr>
                            <tr><td>Biaya Disetujui</td><td class="text-end">{{ $rp($c->biaya_disetujui) }}</td></tr>
                            <tr class="border-top">
                                <td class="fw-bold">Status</td>
                                <td class="text-end">
                                    @if ($c->status == 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Diagnosis & Prosedur --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="file-text" class="me-2 text-primary"></i>Diagnosis & Prosedur</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Diagnosis</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $c->diaglist ? nl2br(e($c->diaglist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Daftar Prosedur</label>
                        <div class="p-3 bg-light rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            {!! $c->proclist ? nl2br(e($c->proclist)) : '<span class="text-muted">-</span>' !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- iDRG --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="bar-chart-2" class="me-2 text-primary"></i>iDRG</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2"><div class="text-muted small">MDC</div><div class="fw-semibold">{{ $c->idrg_mdc_number ? "{$c->idrg_mdc_number} - {$c->idrg_mdc_description}" : '-' }}</div></div>
                    <div class="col-md-3 mb-2"><div class="text-muted small">DRG</div><div class="fw-semibold">{{ $c->idrg_drg_code ? "{$c->idrg_drg_code} - {$c->idrg_drg_description}" : '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Cost Weight</div><div class="fw-semibold">{{ $c->idrg_cost_weight ?: '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Total Cost Weight</div><div class="fw-semibold">{{ $c->idrg_total_cost_weight ?: '-' }}</div></div>
                    <div class="col-md-2 mb-2"><div class="text-muted small">Total Tarif</div><div class="fw-semibold">{{ $rp($c->idrg_total_tarif) }}</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Komponen Biaya RS --}}
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i data-feather="file" class="me-2 text-primary"></i>Komponen Biaya Rumah Sakit</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th class="text-end" style="width: 200px;">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                'Prosedur Non Bedah' => $c->prosedur_non_bedah,
                                'Prosedur Bedah' => $c->prosedur_bedah,
                                'Konsultasi' => $c->konsultasi,
                                'Tenaga Ahli' => $c->tenaga_ahli,
                                'Keperawatan' => $c->keperawatan,
                                'Penunjang' => $c->penunjang,
                                'Radiologi' => $c->radiologi,
                                'Laboratorium' => $c->laboratorium,
                                'Pelayanan Darah' => $c->pelayanan_darah,
                                'Rehabilitasi' => $c->rehabilitasi,
                                'Kamar Akomodasi' => $c->kamar_akomodasi,
                                'Rawat Intensif' => $c->rawat_intensif,
                                'Obat' => $c->obat,
                                'Alkes' => $c->alkes,
                                'BMHP' => $c->bmhp,
                                'Sewa Alat' => $c->sewa_alat,
                                'Obat Kronis' => $c->obat_kronis,
                                'Obat Kemo' => $c->obat_kemo,
                            ] as $label => $nilai)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ $rp($nilai) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>