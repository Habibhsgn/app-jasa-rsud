@extends('layouts.app')

@section('title', 'Index Scoring')

@section('content')
    <h1 class="h3 mb-3"><strong>Index Scoring</strong></h1>

    {{-- Flash messages --}}
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Form Pengajuan --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i data-feather="file-plus" class="me-2"></i>
                Pengajuan Index Scoring
            </h5>
        </div>

        <div class="card-body">
            @php
                $isAdmin = auth()->user()->role?->code === 'admin';
            @endphp

            <form id="formBukaForm" action="{{ route('index.scoring.create') }}" method="GET">
                <input type="hidden" name="mode" value="baru">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Periode Pengajuan</label>
                        <input type="month" name="periode" class="form-control"
                            max="{{ now()->subMonth()->format('Y-m') }}" value="{{ request('periode') }}" required>
                        {{-- <small class="text-muted">Maksimal bulan sebelumnya. Satu ruangan hanya dapat mengajukan satu kali
                            per periode.</small> --}}
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ruangan</label>
                        <select name="ruangan" class="form-select" required>
                            @if ($isAdmin)
                                <option value="">-- Pilih Ruangan --</option>
                            @endif
                            @foreach ($ruanganOptions as $r)
                                <option value="{{ $r->id }}" @selected(request('ruangan') == $r->id)>
                                    {{ $r->nama_ruangan }}
                                </option>
                            @endforeach
                            @if ($isAdmin)
                                <option value="top-leader" @selected(request('ruangan') === 'top-leader')>Top Leader</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success">
                            <i data-feather="plus"></i> Buka Form
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- ============================================================
         KARU (index) & halaman create: render form.
         ============================================================ --}}
    @isset($data)
        @if ($data->count())
            @foreach ($data as $periodeItem)
                @php
                    $collapseId = 'periode-' . $periodeItem->periode . '-' . $loop->index;

                    // Kumpulan status dari semua form dalam periode ini (untuk badge di header)
                    $statuses = $periodeItem->ruangans
                        ->pluck('status_pengajuan')
                        ->merge($periodeItem->top_leaders->pluck('status_pengajuan'))
                        ->unique()
                        ->values();

                    // Terbuka jika hanya 1 periode (mis. baru dibuka lewat "Buka Form")
                    // atau ada form yang masih bisa diedit (draft/revisi). Sisanya tertutup.
                    $needsAction = $statuses->intersect(['draft', 'revisi'])->isNotEmpty();
                    // Admin: semua tertutup (kecuali hanya 1), karu: buka yang perlu tindakan
                    $isOpen = $data->count() === 1 || (!$isAdmin && $needsAction);

                    $jumlahForm = $periodeItem->ruangans->count() + ($periodeItem->top_leaders->count() ? 1 : 0);
                @endphp

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center collapse-toggle"
                        role="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">
                                Periode: {{ $periodeItem->periode_label }}
                                @if ($isAdmin)
                                    <span class="text-muted fw-normal fs-6">
                                        — {{ $periodeItem->ruangans->count() }}
                                        ruangan{{ $periodeItem->top_leaders->count() ? ' + Top Leader' : '' }}
                                    </span>
                                @endif
                            </h5>
                            @foreach ($statuses as $st)
                                @php
                                    $badge = match ($st) {
                                        'revisi' => 'bg-danger',
                                        'draft' => 'bg-secondary',
                                        'submit' => 'bg-primary',
                                        'verifikasi' => 'bg-info',
                                        default => 'bg-dark',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ ucfirst($st) }}</span>
                            @endforeach
                        </div>
                        <i data-feather="chevron-down" class="chevron-icon"></i>
                    </div>

                    <div id="{{ $collapseId }}" class="collapse {{ $isOpen ? 'show' : '' }}">
                        <div class="card-body">

                            {{-- Section 1: Pegawai per Ruangan --}}
                            @foreach ($periodeItem->ruangans as $item)
                                @include('pages.partials.index-scoring-ruangan-form', [
                                    'periodeItem' => $periodeItem,
                                    'item' => $item,
                                ])
                            @endforeach

                            {{-- Section 2: Top Leader (tanpa ruangan, hanya jika dipilih admin) --}}
                            @if ($periodeItem->top_leaders && $periodeItem->top_leaders->count())
                                @foreach ($periodeItem->top_leaders as $group)
                                    @include('pages.partials.index-scoring-top-leader-form', [
                                        'periodeItem' => $periodeItem,
                                        'group' => $group,
                                    ])
                                @endforeach
                            @endif

                        </div> {{-- /card-body --}}
                    </div> {{-- /collapse --}}
                </div> {{-- /card periode --}}
            @endforeach

            @isset($list)
                <div class="mt-3">{{ $list->links() }}</div>
            @endisset
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i data-feather="clipboard" style="width:70px;height:70px" class="text-secondary mb-3"></i>
                    <h5 class="mb-2">Belum Ada Pengajuan</h5>
                    <p class="text-muted mb-0">
                        Pilih periode dan ruangan terlebih dahulu, kemudian klik
                        <strong>Buka Form</strong>.
                    </p>
                </div>
            </div>
        @endif

        {{-- Script Kalkulasi Index Scoring + Validasi Submit (hanya jika ada form) --}}
        @include('pages.partials.index-scoring-scripts')
    @endisset

    {{-- Modal konfirmasi data anggota sebelum membuka form --}}
    <div class="modal fade" id="modalKonfirmasiData" tabindex="-1" aria-labelledby="modalKonfirmasiDataLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalKonfirmasiDataLabel">
                        <i data-feather="alert-triangle" class="me-2 text-warning"></i>
                        Konfirmasi Data Sebelum Mengisi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">
                        Anda akan membuka form untuk <strong id="konfirmasiRuangan">-</strong>
                        periode <strong id="konfirmasiPeriode">-</strong>.
                    </p>
                    <p class="text-muted">
                        Hasil index scoring dihitung dari data berikut. Mohon pastikan sudah benar sebelum melanjutkan:
                    </p>

                    <div class="form-check mb-2">
                        <input class="form-check-input konfirmasi-check" type="checkbox" id="chk1">
                        <label class="form-check-label" for="chk1">
                            <strong>NIP / ID Petugas dan nama</strong> seluruh anggota sudah sesuai.
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input konfirmasi-check" type="checkbox" id="chk2">
                        <label class="form-check-label" for="chk2">
                            <strong>Gaji pokok</strong> dan komponen lainnya (jabatan, pendidikan formal &amp; non formal,
                            risk, emergency) sudah sesuai.
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input konfirmasi-check" type="checkbox" id="chk3">
                        <label class="form-check-label" for="chk3">
                            <strong>Keanggotaan sudah sesuai</strong>: seluruh anggota masih berada di ruangan ini
                            (belum mutasi/pindah, pensiun, atau resign) dan tidak ada anggota baru yang belum tercatat.
                        </label>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        Jika ada data yang tidak sesuai, perbaiki terlebih dahulu di data master pegawai atau hubungi
                        admin, kemudian buka form ini kembali.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnLanjutkanBukaForm" disabled>
                        Data Sudah Sesuai, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formBukaForm');
            const modalEl = document.getElementById('modalKonfirmasiData');
            if (!form || !modalEl) return;

            const checks = modalEl.querySelectorAll('.konfirmasi-check');
            const btnLanjut = document.getElementById('btnLanjutkanBukaForm');

            const namaBulan = (ym) => {
                const [y, m] = ym.split('-').map(Number);
                return new Date(y, m - 1, 1).toLocaleDateString('id-ID', {
                    month: 'long',
                    year: 'numeric'
                });
            };

            const refreshButton = () => {
                btnLanjut.disabled = !Array.from(checks).every(c => c.checked);
            };

            // Submit form -> tampilkan modal dulu (validasi bawaan browser sudah lolos di titik ini)
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const sel = form.querySelector('[name="ruangan"]');
                const periode = form.querySelector('[name="periode"]');

                document.getElementById('konfirmasiRuangan').textContent =
                    sel.options[sel.selectedIndex].text.trim();
                document.getElementById('konfirmasiPeriode').textContent = namaBulan(periode.value);

                checks.forEach(c => c.checked = false);
                refreshButton();

                if (window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else if (confirm(
                        'Pastikan NIP, gaji pokok, komponen scoring, dan keanggotaan ruangan sudah sesuai. Lanjutkan?'
                    )) {
                    form.submit();
                }
            });

            checks.forEach(c => c.addEventListener('change', refreshButton));

            btnLanjut.addEventListener('click', function() {
                if (btnLanjut.disabled) return;
                form.submit(); // submit() langsung tidak memicu event 'submit' lagi
            });
        });
    </script>

    <style>
        .collapse-toggle {
            cursor: pointer;
            user-select: none;
        }

        .collapse-toggle .chevron-icon {
            transition: transform .2s ease;
        }

        /* V saat tertutup, ^ saat terbuka */
        .collapse-toggle[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }
    </style>
@endsection
