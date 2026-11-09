{{-- Tabel INA-CBG. Dipanggil dari index.blade.php (memakai variabel milik index). --}}
@php $formatUang = fn($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2, ',', '.'); @endphp
<div class="card">
    <div class="card-header bg-white">
        <div class="text-muted small">
            Menampilkan {{ $claims->firstItem() ?? 0 }} - {{ $claims->lastItem() ?? 0 }}
            dari {{ $claims->total() }} data
            @if ($isSection)
                <span class="ms-1">· {{ $cfg['label'] }}</span>
            @elseif ($isRaber)
                <span class="ms-1">· Raber</span>
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
                            <th><a href="{{ $sortUrl('sep') }}"
                                    class="text-dark text-decoration-none">SEP{{ $sortMark('sep') }}</a></th>
                            <th><a href="{{ $sortUrl('nama_pasien') }}" class="text-dark text-decoration-none">Nama
                                    Pasien{{ $sortMark('nama_pasien') }}</a></th>
                            <th>MRN</th>
                            <th>INA-CBG</th>
                            <th><a href="{{ $sortUrl('total_tarif') }}" class="text-dark text-decoration-none">Total
                                    Tarif{{ $sortMark('total_tarif') }}</a></th>
                            <th>Biaya Diajukan</th>
                            <th>Biaya Disetujui</th>
                            <th>Tgl. Verifikasi</th>
                            <th><a href="{{ $sortUrl('status') }}"
                                    class="text-dark text-decoration-none">Status{{ $sortMark('status') }}</a></th>
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
                                <td>{{ $claim->total_tarif ? $formatUang($claim->total_tarif) : '-' }}</td>
                                <td>{{ $claim->biaya_diajukan ? $formatUang($claim->biaya_diajukan) : '-' }}</td>
                                <td>{{ $claim->biaya_disetujui ? $formatUang($claim->biaya_disetujui) : '-' }}
                                </td>
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
                                                title="{{ $s['label'] }} diisi oleh {{ $ket[$k]['user_name'] ?? '-' }}">&#10003;
                                                {{ $s['short'] }}</span>
                                        @elseif ($hasCost)
                                            @php $chips++; @endphp
                                            <span
                                                class="badge rounded-pill bg-transparent border border-{{ $s['color'] }} text-{{ $s['color'] }} me-1"
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
                @elseif ($isRaber)
                    {{-- ===== Tab Raber ===== --}}
                    <thead class="table-secondary">
                        <tr>
                            <th><a href="{{ $sortUrl('sep') }}"
                                    class="text-dark text-decoration-none">SEP{{ $sortMark('sep') }}</a></th>
                            <th><a href="{{ $sortUrl('nama_pasien') }}"
                                    class="text-dark text-decoration-none">Pasien{{ $sortMark('nama_pasien') }}</a></th>
                            <th><a href="{{ $sortUrl('discharge_date') }}" class="text-dark text-decoration-none">Tgl.
                                    Pulang{{ $sortMark('discharge_date') }}</a></th>
                            <th>DPJP (dokter utama di atas)</th>
                            <th>Status DPJP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            @php
                                $docs = \App\Models\InacbgClaim::splitDpjp($claim->dpjp);
                                $diubah = !empty($claim->dpjp_asli);
                            @endphp
                            <tr>
                                <td>{{ $claim->sep ?: '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $claim->nama_pasien ?: '-' }}</div>
                                    <div class="small text-muted">MRN {{ $claim->mrn ?: '-' }}</div>
                                </td>
                                <td>{{ $claim->discharge_date ? $claim->discharge_date->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @foreach ($docs as $i => $dokter)
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            @if ($i === 0)
                                                <span class="badge text-bg-info">Utama</span>
                                                <span class="fw-semibold">{{ $dokter }}</span>
                                            @else
                                                <span class="badge text-bg-light border text-muted">{{ $i + 1 }}</span>
                                                <span>{{ $dokter }}</span>
                                                @if ($canRaber)
                                                    <form action="{{ route('inacbg.update-dpjp-utama', $claim) }}"
                                                        method="POST" data-ajax class="d-inline"
                                                        onsubmit="return confirm(@js('Jadikan ' . $dokter . ' sebagai dokter utama?'))">
                                                        @csrf @method('PUT')
                                                        <input type="hidden" name="dokter" value="{{ $dokter }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-info py-0">Jadikan
                                                            utama</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($diubah)
                                        <span class="badge text-bg-warning"
                                            title="Asli: {{ $claim->dpjp_asli }}&#10;Diubah oleh {{ $claim->dpjp_diubah_oleh ?? '-' }}{{ $claim->dpjp_diubah_at ? ' pada ' . $claim->dpjp_diubah_at->format('d/m/Y H:i') : '' }}">Diubah</span>
                                        <div class="small text-muted mt-1">
                                            {{ $claim->dpjp_diubah_oleh ?? '-' }}
                                            @if ($claim->dpjp_diubah_at)
                                                &middot; {{ $claim->dpjp_diubah_at->format('d/m/Y H:i') }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge text-bg-secondary">Default</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($diubah && $canRaber)
                                        <form action="{{ route('inacbg.reset-dpjp', $claim) }}" method="POST" data-ajax
                                            class="d-inline" onsubmit="return confirm('Kembalikan DPJP ke default?')">
                                            @csrf @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Reset</button>
                                        </form>
                                    @endif
                                    @if ($canAll)
                                        <a href="{{ route('inacbg.show', [$claim, 'tab' => 'semua']) }}"
                                            class="btn btn-sm btn-outline-primary">Detail</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Tidak ada SEP rawat bersama pada filter ini.
                                    <br><small>Coba ganti bulan, tahun, atau filter status DPJP.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                @else
                    {{-- ===== Tab seksi ===== --}}
                    <thead class="table-secondary">
                        <tr>
                            <th><a href="{{ $sortUrl('sep') }}"
                                    class="text-dark text-decoration-none">SEP{{ $sortMark('sep') }}</a></th>
                            <th><a href="{{ $sortUrl('nama_pasien') }}"
                                    class="text-dark text-decoration-none">Pasien{{ $sortMark('nama_pasien') }}</a>
                            </th>
                            <th><a href="{{ $sortUrl('discharge_date') }}" class="text-dark text-decoration-none">Tgl.
                                    Pulang{{ $sortMark('discharge_date') }}</a></th>
                            @if ($tab === 'radiologi')
                                <th>Tindakan</th>
                            @endif
                            <th class="text-end"><a href="{{ $sortUrl($cfg['column']) }}"
                                    class="text-dark text-decoration-none">Biaya
                                    {{ $cfg['label'] }}{{ $sortMark($cfg['column']) }}</a></th>
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
                                @if ($tab === 'radiologi')
                                    <td class="text-nowrap">
                                        @if ($canEditTab)
                                            {{-- Pilih satu atau kedua tindakan langsung dari baris tabel --}}
                                            <form action="{{ route('inacbg.update-tindakan', $claim) }}" method="POST"
                                                data-ajax class="d-flex align-items-center gap-2"
                                                aria-label="Tindakan radiologi">
                                                @csrf @method('PUT')
                                                <div class="btn-group btn-group-sm" role="group">
                                                    @foreach (\App\Models\InacbgClaim::TINDAKAN_RADIOLOGI as $k => $label)
                                                        <input type="checkbox" class="btn-check" name="tindakan[]"
                                                            value="{{ $k }}" id="tindakan-{{ $claim->id }}-{{ $k }}"
                                                            autocomplete="off"
                                                            onchange="if (this.form.querySelector('input[type=checkbox]:checked')) this.form.requestSubmit(); else this.checked = true;"
                                                            {{ in_array($k, $claim->radiologiTindakanList(), true) ? 'checked' : '' }}>
                                                        <label class="btn btn-outline-primary" for="tindakan-{{ $claim->id }}-{{ $k }}">
                                                            {{ $label }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </form>
                                        @elseif ($claim->radiologi_tindakan)
                                            <span
                                                class="badge text-bg-primary">{{ $claim->radiologiTindakanLabel() }}</span>
                                        @else
                                            <span class="badge text-bg-secondary">Belum dipilih</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-end">Rp {{ $formatUang($claim->{$cfg['column']}) }}</td>
                                <td>
                                    @if ($ket)
                                        <div>{{ \Illuminate\Support\Str::limit($ket['teks'] ?? '', 90) }}</div>
                                        <div class="small text-muted">
                                            {{ $ket['user_name'] ?? '-' }}
                                            @if (!empty($ket['updated_at']))
                                                &middot;
                                                {{ \Carbon\Carbon::parse($ket['updated_at'])->format('d/m/Y H:i') }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge text-bg-warning">Belum diisi</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canEditTab)
                                        <a href="{{ route('inacbg.show', [$claim, 'tab' => $tab]) }}"
                                            @if ($tab === 'radiologi' && !$claim->radiologi_tindakan) title="Pilih tindakan (Rontgen / USG) dulu" @endif
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
                                <td colspan="{{ $tab === 'radiologi' ? 7 : 6 }}" class="text-center py-5 text-muted">
                                    Tidak ada SEP dengan biaya {{ $cfg['label'] }} pada filter ini.
                                    <br>
                                    <small>Coba ganti bulan, tahun, atau hapus filter "Belum diisi" / "Sudah
                                        diisi".</small>
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
