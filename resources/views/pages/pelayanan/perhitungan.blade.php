@extends('layouts.app')
@section('title', 'Perhitungan Pelayanan')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0"><strong>Perhitungan</strong> Pelayanan</h1>
            <p class="text-muted mb-0 small">Alokasi jasa untuk staf pelayanan berdasarkan bobot Index Scoring (periode
                klaim INA-CBG)</p>
        </div>
    </div>

    <div class="container-fluid p-0">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filter Periode -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 text-muted">Filter Periode</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-select">
                            <option value="">Semua Bulan</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->locale('id')->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            Filter
                        </button>
                        <a href="{{ route('pelayanan.perhitungan') }}" class="btn btn-outline-secondary flex-fill">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Total Jasa Disetujui</h6>
                        <h4 class="mb-1">Rp {{ number_format($totalJasaDisetujui, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">
                            {{ $bulan ? \Carbon\Carbon::create()->month((int) $bulan)->format('F') : 'Semua Bulan' }}
                            {{ $tahun ?: 'Semua Tahun' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Nilai Persen Jasa ({{ $persenJasa * 100 }}%)</h6>
                        <h4 class="mb-1">Rp {{ number_format($nilaiPersenJasa, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">Total Jasa &times; {{ $persenJasa * 100 }}%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-2 text-uppercase small">Total Pelayanan ({{ $persenStaff * 100 }}%)</h6>
                        <h4 class="mb-1">Rp {{ number_format($totalPelayanan, 0, ',', '.') }}</h4>
                        <span class="badge text-bg-light text-muted fw-normal">Nilai Persen Jasa &times;
                            {{ $persenStaff * 100 }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Bobot Index Scoring -->
        <div class="card mb-4 shadow-sm border-start border-success border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">Total Bobot Index Scoring (Jumlah Akhir)</h6>
                        @if ($totalBobot > 0)
                            <p class="mb-0 text-muted small">Penjumlahan <strong>Jumlah Akhir</strong> dari semua Pegawai
                                yang status <span class="badge bg-primary">Selesai</span> pada periode ini</p>
                        @else
                            <p class="mb-0 text-warning"><i class="bi bi-exclamation-triangle me-1"></i> <strong>Index
                                    Scoring belum diajukan</strong> untuk periode ini. Alokasi per posisi dihitung dari
                                INA-CBG, tapi distribusi ke individu memerlukan Index Scoring status Selesai.</p>
                        @endif
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h3 class="mb-0 {{ $totalBobot > 0 ? 'text-success' : 'text-muted' }}">
                            {{ number_format($totalBobot, 2, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Perhitungan per Posisi -->
        @if ($totalBobot > 0)
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Alokasi per Posisi (Berdasarkan Bobot Index Scoring)</h5>
                    <span class="badge text-bg-secondary">{{ count($calculations) }} Posisi</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Posisi</th>
                                    <th class="text-end">Total Bobot</th>
                                    <th class="text-end">% Bobot</th>
                                    <th class="text-end">Alokasi Total</th>
                                    <th class="text-center">Orang</th>
                                    <th class="text-end">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($calculations as $posisi => $data)
                                    <tr>
                                        <td>
                                            <strong>{{ $posisi }}</strong>
                                            @if ($data['is_medis_paramedis'] ?? false)
                                                <span class="badge text-bg-info ms-2">Akan dipecah</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($data['total_bobot'], 2, ',', '.') }}</td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 6px; width: 100px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: {{ $data['persen'] }}%"
                                                    aria-valuenow="{{ $data['persen'] }}" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small
                                                class="text-muted">{{ number_format($data['persen'], 2, ',', '.') }}%</small>
                                        </td>
                                        <td class="text-end"><strong>Rp
                                                {{ number_format($data['alokasi_total'], 0, ',', '.') }}</strong></td>
                                        <td class="text-center">
                                            @if ($data['is_medis_paramedis'])
                                                <span class="text-muted fw-bold">-</span>
                                            @elseif ($data['jumlah_orang'] > 0)
                                                <span
                                                    class="badge rounded-pill text-bg-success">{{ $data['jumlah_orang'] }}</span>
                                            @else
                                                <span class="badge rounded-pill text-bg-warning">Kosong</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($data['is_medis_paramedis'])
                                                <span class="text-muted fw-bold">-</span>
                                            @else
                                                Rp {{ number_format($data['per_orang_rata'], 0, ',', '.') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="text-end">{{ number_format($totalBobot, 2, ',', '.') }}</th>
                                    <th class="text-end">100%</th>
                                    <th class="text-end">Rp {{ number_format($totalPelayanan, 0, ',', '.') }}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detail Per Orang - Grouped by Ruangan with Collapse -->
            <div class="mt-4">
                @foreach ($calculations as $posisi => $data)
                    @if (!$data['is_medis_paramedis'] && !empty($data['detail_by_ruangan']))
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Detail Alokasi - {{ $posisi }}</h5>
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="toggleAll('{{ $posisi }}')"
                                        id="toggle-btn-{{ str_replace([' ', '/', '.'], '_', $posisi) }}">
                                    <i class="bi bi-chevron-double-up me-1"></i>
                                    <span class="toggle-text">Tutup Semua</span>
                                </button>
                            </div>
                            <div class="card-body p-0">
                                @foreach ($data['detail_by_ruangan'] as $ruanganNama => $anggota)
                                    <div class="border-bottom">
                                        <!-- Ruangan Header (Collapsible) -->
                                        <div class="bg-light p-3">
                                            <button type="button"
                                                    class="btn btn-link text-decoration-none text-dark fw-bold d-flex justify-content-between align-items-center w-100"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#collapse-{{ str_replace([' ', '/', '.'], '_', $posisi) }}-{{ str_replace([' ', '/', '.'], '_', $ruanganNama) }}"
                                                    aria-expanded="true">
                                                <span>
                                                    <i class="bi bi-chevron-down me-2 collapse-icon"></i>
                                                    {{ $ruanganNama }}
                                                    <span class="badge text-bg-primary ms-2">{{ count($anggota) }} Orang</span>
                                                </span>
                                                <span class="text-muted small">
                                                    Total: Rp {{ number_format(array_sum(array_column($anggota, 'alokasi')), 0, ',', '.') }}
                                                </span>
                                            </button>
                                        </div>

                                        <!-- Ruangan Detail Table (Collapsible Content) -->
                                        <div class="collapse show"
                                             id="collapse-{{ str_replace([' ', '/', '.'], '_', $posisi) }}-{{ str_replace([' ', '/', '.'], '_', $ruanganNama) }}">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Nama</th>
                                                            <th>Posisi</th>
                                                            <th>Bidang</th>
                                                            <th class="text-end">Gaji</th>
                                                            <th class="text-end">Bobot</th>
                                                            <th class="text-end">% Bobot</th>
                                                            <th class="text-end"><strong>Alokasi</strong></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($anggota as $i => $a)
                                                            <tr class="{{ $a['ruangan_belum_di_set'] ? 'table-warning' : '' }}">
                                                                <td>{{ $i + 1 }}</td>
                                                                <td><strong>{{ $a['nama'] }}</strong></td>
                                                                <td><span class="badge text-bg-info">{{ $a['posisi'] }}</span></td>
                                                                <td>
                                                                    @if ($a['ruangan_belum_di_set'])
                                                                        <span class="text-warning fw-bold">
                                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                                            Ruangan belum di-set bidang
                                                                        </span>
                                                                    @elseif ($a['bidang'])
                                                                        <span class="badge text-bg-primary">{{ $a['bidang'] }}</span>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end">Rp {{ number_format($a['gaji_pokok'], 0, ',', '.') }}</td>
                                                                <td class="text-end">{{ number_format($a['jumlah_akhir'], 2, ',', '.') }}</td>
                                                                <td class="text-end">
                                                                    @if ($a['jumlah_akhir'] > 0)
                                                                        <span
                                                                            class="badge text-bg-success">{{ number_format($a['bobot_persen_posisi'], 2, ',', '.') }}%</span>
                                                                    @else
                                                                        <span class="badge text-bg-secondary">Belum ada Index Scoring</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end">
                                                                    @if ($a['alokasi'] > 0)
                                                                        <strong class="text-primary">Rp
                                                                            {{ number_format($a['alokasi'], 0, ',', '.') }}</strong>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <th colspan="6" class="text-end">Sub Total {{ $ruanganNama }}</th>
                                                            <th class="text-end">{{ number_format(array_sum(array_column($anggota, 'bobot_persen_posisi')), 2, ',', '.') }}%</th>
                                                            <th class="text-end"><strong>Rp {{ number_format(array_sum(array_column($anggota, 'alokasi')), 0, ',', '.') }}</strong></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Catatan Medis & Paramedis -->
            @if (isset($calculations['Medis & Paramedis']) && $calculations['Medis & Paramedis']['is_medis_paramedis'])
                <div class="card mt-4 shadow-sm border-start border-info border-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle-fill fs-4 text-info me-3"></i>
                            <div>
                                <h6 class="mb-1">Medis & Paramedis</h6>
                                <p class="mb-0 text-muted">
                                    Alokasi total: <strong>Rp {{ number_format($calculations['Medis & Paramedis']['alokasi_total'], 0, ',', '.') }}</strong>
                                    <br class="d-none d-md-block">
                                    Bidang ini akan dipecah lebih detail di halaman terpisah (mengikuti struktur ruangan/bidang).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @else
            <div class="card mt-4 shadow-sm border-start border-warning border-4">
                <div class="card-body text-center py-5">
                    <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3"></i>
                    <h5 class="mb-2">Index Scoring Belum Diajukan</h5>
                    <p class="text-muted mb-3">Belum ada data Index Scoring Pegawai dengan status <span
                            class="badge bg-primary">Selesai</span> untuk periode ini.</p>
                    <p class="text-muted mb-0">Silakan isi Index Scoring terlebih dahulu agar alokasi per individu dapat
                        dihitung.</p>
                </div>
            </div>
        @endif

        <!-- Rumus Perhitungan -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Cara Perhitungan (Berdasarkan Index Scoring)</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">
                        <strong>Total Jasa Disetujui</strong> &mdash; Jumlah seluruh biaya klaim yang statusnya
                        sudah <em>disetujui</em>
                        @if ($bulan || $tahun)
                            , untuk periode
                            <strong>
                                {{ $bulan ? \Carbon\Carbon::create()->month((int) $bulan)->format('F') : 'semua bulan' }}
                                {{ $tahun ?: 'semua tahun' }}
                            </strong>
                        @else
                            dari seluruh periode
                        @endif
                        .
                    </li>
                    <li class="mb-2">
                        <strong>Nilai Persen Jasa</strong> &mdash; Total Jasa Disetujui dikalikan
                        {{ $persenJasa * 100 }}% (persentase ini diatur lewat pengaturan
                        <code>persen_jasa</code>).
                    </li>
                    <li class="mb-2">
                        <strong>Total Pelayanan</strong> &mdash; Nilai Persen Jasa di atas dikalikan lagi
                        {{ $persenStaff * 100 }}% (diatur lewat pengaturan <code>persen_jasa_staff</code>).
                        Angka inilah yang menjadi dana yang akan dibagi ke seluruh staf pelayanan.
                    </li>
                    <li class="mb-2">
                        <strong>Bobot Index Scoring</strong> &mdash; Ambil data <strong>Index Scoring</strong> semua Pegawai
                        yang status <span class="badge bg-primary">Selesai</span> pada periode yang sama.
                        Kolom <strong>Jumlah Akhir</strong> menjadi bobot masing-masing individu.
                    </li>
                    <li class="mb-2">
                        <strong>Total Bobot</strong> &mdash; Penjumlahan Jumlah Akhir semua Pegawai.
                    </li>
                    <li class="mb-2">
                        <strong>Alokasi per Individu</strong> &mdash; <code>(Jumlah Akhir Individu / Total Bobot) &times; Total Pelayanan</code>.
                        <br>Contoh: Staf A (11.2) + Staf B (10) = Total 21.2 &rarr; Pool 65.000.000
                        <br>A dapat: 11.2/21.2 &times; 65.000.000 = <strong>34.339.623</strong>
                        <br>B dapat: 10/21.2 &times; 65.000.000 = <strong>30.660.377</strong>
                    </li>
                    <li>
                        <strong>Catatan:</strong> Pegawai yang belum punya Index Scoring "Selesai" pada periode ini
                        mendapat bobot 0 (tidak mendapat alokasi).
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        // Toggle collapse icon rotation
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const icon = this.querySelector('.collapse-icon');
                const target = document.querySelector(this.dataset.bsTarget);
                if (target) {
                    target.addEventListener('shown.bs.collapse', () => {
                        icon.classList.remove('bi-chevron-down');
                        icon.classList.add('bi-chevron-up');
                    });
                    target.addEventListener('hidden.bs.collapse', () => {
                        icon.classList.remove('bi-chevron-up');
                        icon.classList.add('bi-chevron-down');
                    });
                }
            });
        });

        function toggleAll(posisi) {
            const safePosisi = posisi.replace(/[ \/\\.]/g, '_');
            const collapseElements = document.querySelectorAll('[id^="collapse-' + safePosisi + '-"]');
            const btn = document.getElementById('toggle-btn-' + safePosisi);
            const icon = btn?.querySelector('i');
            const text = btn?.querySelector('.toggle-text');

            // Check if any is open
            const anyOpen = Array.from(collapseElements).some(el => el.classList.contains('show'));

            collapseElements.forEach(el => {
                if (anyOpen && el.classList.contains('show')) {
                    new bootstrap.Collapse(el, { toggle: true });
                } else if (!anyOpen && !el.classList.contains('show')) {
                    new bootstrap.Collapse(el, { toggle: true });
                }
            });

            // Update button
            if (btn && icon && text) {
                if (anyOpen) {
                    icon.classList.remove('bi-chevron-double-up');
                    icon.classList.add('bi-chevron-double-down');
                    text.textContent = 'Buka Semua';
                } else {
                    icon.classList.remove('bi-chevron-double-down');
                    icon.classList.add('bi-chevron-double-up');
                    text.textContent = 'Tutup Semua';
                }
            }
        }
    </script>
@endsection