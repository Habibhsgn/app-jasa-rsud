{{-- Tabel INA-CBG. Dipanggil dari index.blade.php (memakai variabel milik index). --}}
<div class="card">
    <div class="card-header bg-white">
        <div class="text-muted small">
            Menampilkan {{ $claims->firstItem() ?? 0 }} - {{ $claims->lastItem() ?? 0 }}
            dari {{ $claims->total() }} data
            @if (!$isAll)
                <span class="ms-1">· {{ $cfg['label'] }}</span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                @if ($isAll)
                    {{-- ===== Tab Semua ===== --}}
                    <thead class="table-secondary">
                        <tr>
                            <th><a href="{{ $sortUrl('sep') }}" class="text-dark text-decoration-none">SEP{{ $sortMark('sep') }}</a></th>
                            <th><a href="{{ $sortUrl('nama_pasien') }}" class="text-dark text-decoration-none">Nama Pasien{{ $sortMark('nama_pasien') }}</a></th>
                            <th>MRN</th>
                            <th>INA-CBG</th>
                            <th><a href="{{ $sortUrl('total_tarif') }}" class="text-dark text-decoration-none">Total Tarif{{ $sortMark('total_tarif') }}</a></th>
                            <th>Biaya Diajukan</th>
                            <th>Biaya Disetujui</th>
                            <th>Tgl. Verifikasi</th>
                            <th><a href="{{ $sortUrl('status') }}" class="text-dark text-decoration-none">Status{{ $sortMark('status') }}</a></th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            @php $ket = $claim->keterangan ?? []; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}" class="text-primary">
                                        {{ $claim->sep ?: '-' }}
                                    </a>
                                </td>
                                <td class="fw-semibold">{{ $claim->nama_pasien ?: '-' }}</td>
                                <td>{{ $claim->mrn ?: '-' }}</td>
                                <td><code>{{ $claim->inacbg ?: '-' }}</code></td>
                                <td>{{ $claim->total_tarif ? number_format($claim->total_tarif, 0) : '-' }}</td>
                                <td>{{ $claim->biaya_diajukan ? number_format($claim->biaya_diajukan, 0) : '-' }}</td>
                                <td>{{ $claim->biaya_disetujui ? number_format($claim->biaya_disetujui, 0) : '-' }}</td>
                                <td>{{ $claim->tgl_verifikasi ? $claim->tgl_verifikasi->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if ($claim->status == 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @php $chips = 0; @endphp
                                    @foreach ($sectionList as $k => $s)
                                        @php
                                            $hasCost = (float) $claim->{$s['column']} > 0;
                                            $filled = !empty($ket[$k]);
                                        @endphp
                                        @if ($filled)
                                            @php $chips++; @endphp
                                            <span class="badge rounded-pill text-bg-{{ $s['color'] }} me-1"
                                                title="{{ $s['label'] }} diisi oleh {{ $ket[$k]['user_name'] ?? '-' }}">&#10003; {{ $s['short'] }}</span>
                                        @elseif ($hasCost)
                                            @php $chips++; @endphp
                                            <span class="badge rounded-pill bg-transparent border border-{{ $s['color'] }} text-{{ $s['color'] }} me-1"
                                                title="{{ $s['label'] }}: belum diisi">{{ $s['short'] }}</span>
                                        @endif
                                    @endforeach
                                    @if ($chips === 0)
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}"
                                        class="btn btn-sm btn-outline-primary" title="Detail">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    Belum ada data INA-CBG.
                                    <br>
                                    <small>Silakan upload file Excel menggunakan form di atas.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @else
                    {{-- ===== Tab seksi ===== --}}
                    <thead class="table-secondary">
                        <tr>
                            <th><a href="{{ $sortUrl('sep') }}" class="text-dark text-decoration-none">SEP{{ $sortMark('sep') }}</a></th>
                            <th><a href="{{ $sortUrl('nama_pasien') }}" class="text-dark text-decoration-none">Pasien{{ $sortMark('nama_pasien') }}</a></th>
                            <th><a href="{{ $sortUrl('discharge_date') }}" class="text-dark text-decoration-none">Tgl. Pulang{{ $sortMark('discharge_date') }}</a></th>
                            <th class="text-end"><a href="{{ $sortUrl($cfg['column']) }}" class="text-dark text-decoration-none">Biaya {{ $cfg['label'] }}{{ $sortMark($cfg['column']) }}</a></th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            @php $ket = $claim->keterangan[$tab] ?? null; @endphp
                            <tr class="{{ $ket ? '' : 'table-warning' }}">
                                <td>
                                    <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}" class="text-primary">
                                        {{ $claim->sep ?: '-' }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $claim->nama_pasien ?: '-' }}</div>
                                    <div class="small text-muted">MRN {{ $claim->mrn ?: '-' }}</div>
                                </td>
                                <td>{{ $claim->discharge_date ? $claim->discharge_date->format('d/m/Y') : '-' }}</td>
                                <td class="text-end">Rp {{ number_format($claim->{$cfg['column']}, 0) }}</td>
                                <td>
                                    @if ($ket)
                                        <div>{{ \Illuminate\Support\Str::limit($ket['teks'] ?? '', 90) }}</div>
                                        <div class="small text-muted">
                                            {{ $ket['user_name'] ?? '-' }}
                                            @if (!empty($ket['updated_at']))
                                                &middot; {{ \Carbon\Carbon::parse($ket['updated_at'])->format('d/m/Y H:i') }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge text-bg-warning">Belum diisi</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canEditTab)
                                        <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}"
                                            class="btn btn-sm {{ $ket ? 'btn-outline-' . $cfg['color'] : 'btn-' . $cfg['color'] }}">
                                            {{ $ket ? 'Ubah' : 'Isi keterangan' }}
                                        </a>
                                    @else
                                        <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}"
                                            class="btn btn-sm btn-outline-secondary">Lihat</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Tidak ada SEP dengan biaya {{ $cfg['label'] }} pada filter ini.
                                    <br>
                                    <small>Coba ganti bulan, tahun, atau hapus filter "Belum diisi" / "Sudah diisi".</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
    </div>
    @if ($claims->hasPages())
        <div class="card-footer bg-white">
            {{ $claims->links() }}
        </div>
    @endif
</div>