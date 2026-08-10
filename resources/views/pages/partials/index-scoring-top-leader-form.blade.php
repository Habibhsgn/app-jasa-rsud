<div class="card mb-4 border-info">
    <div
        class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom bg-light">
        <div>
            <span class="text-muted small">Top Leader - {{ $group->posisi }}:</span>
            <strong>{{ $group->leaders->count() }} orang</strong>
        </div>

        {{-- Badge status --}}
        <div>
            @if ($group->status_pengajuan == 'draft')
                <span class="badge bg-warning">Draft</span>
            @elseif($group->status_pengajuan == 'submit')
                <span class="badge bg-success">Sudah Submit</span>
            @elseif($group->status_pengajuan == 'verifikasi')
                <span class="badge bg-info text-dark">Menunggu Verifikasi</span>
            @elseif($group->status_pengajuan == 'revisi')
                <span class="badge bg-danger">Perlu Revisi — Silakan lengkapi & submit ulang</span>
            @elseif($group->status_pengajuan == 'selesai')
                <span class="badge bg-primary">Selesai</span>
            @endif
        </div>
    </div>

    <div class="card-body">
        <form action="{{ route('index.scoring.store') }}" method="POST">
            @csrf
            {{-- Top Leader tidak punya ruangan --}}
            <input type="hidden" name="jasa_ruangan_id" value="">
            <input type="hidden" name="periode" value="{{ $periodeItem->periode }}">

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle text-center" style="min-width:70px;">
                                NO</th>
                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:250px;">NAMA</th>

                            <th colspan="7" class="align-middle text-center">INDEKS SKOR</th>
                            <th colspan="4" class="align-middle text-center">BOBOT PENGURANG</th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:220px;">
                                SIKAP<br>
                                <small class="fw-normal">
                                    (Kriminal, Narkoba,<br>Asusila, Merokok, dll)
                                </small>
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:170px;">
                                JUMLAH<br>
                                <small class="fw-normal">
                                    (Total Skor Pengurangan)
                                </small>
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:180px;">
                                Keterangan
                            </th>

                            <th rowspan="2" class="align-middle text-center"
                                style="min-width:170px;">
                                JUMLAH AKHIR
                            </th>
                        </tr>

                        <tr>
                            <th class="align-middle text-center" style="min-width:120px;">Posisi</th>
                            <th class="align-middle text-center" style="min-width:160px;">Jabatan</th>
                            <th class="align-middle text-center" style="min-width:120px;">
                                Pend.<br>Formal</th>
                            <th class="align-middle text-center" style="min-width:140px;">Pend.
                                Non<br>Formal</th>
                            <th class="align-middle text-center" style="min-width:120px;">Gaji<br>Pokok
                            </th>
                            <th class="align-middle text-center" style="min-width:110px;">Risk</th>
                            <th class="align-middle text-center" style="min-width:120px;">Emergency</th>

                            <th class="align-middle text-center" style="min-width:100px;">Cuti</th>
                            <th class="align-middle text-center" style="min-width:100px;">Izin</th>
                            <th class="align-middle text-center" style="min-width:120px;">Tanpa<br>Izin
                            </th>
                            <th class="align-middle text-center" style="min-width:100px;">Telat</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($group->leaders as $i => $tl)
                            <tr>
                                <td>{{ $i + 1 }}</td>

                                <input type="hidden" name="pegawai[{{ $tl->source_id }}][ruangan_id]" value="">
                                <input type="hidden" name="pegawai[{{ $tl->source_id }}][pegawai_id]" value="">
                                <input type="hidden" name="pegawai[{{ $tl->source_id }}][source_type]" value="top_leader">
                                <input type="hidden" name="pegawai[{{ $tl->source_id }}][source_id]" value="{{ $tl->source_id }}">

                                <td class="text-start">
                                    <strong>{{ $tl->nama }}</strong>
                                    <br><small class="text-muted">{{ $tl->posisi }}</small>
                                </td>

                                <td>
                                    <span class="badge bg-info">{{ $tl->posisi }}</span>
                                </td>

                                <td>
                                    <select name="pegawai[{{ $tl->source_id }}][jabatan]"
                                        class="form-select form-select-sm jabatan"
                                        {{ $group->disable_input ? 'disabled' : '' }}>

                                        <option value="0"
                                            {{ $tl->jabatan == 0 ? 'selected' : '' }}>-- Pilih Jabatan
                                            --</option>
                                        <option value="1"
                                            {{ $tl->jabatan == 1 ? 'selected' : '' }}>(1) Tidak memiliki
                                            jabatan</option>
                                        <option value="2"
                                            {{ $tl->jabatan == 2 ? 'selected' : '' }}>(2) Koordinator,
                                            Admin, Penanggung Jawab, Bendahara</option>
                                        <option value="3"
                                            {{ $tl->jabatan == 3 ? 'selected' : '' }}>(3) Kepala
                                            Seksi/Sub Bidang, Kepala Ruangan</option>
                                        <option value="4"
                                            {{ $tl->jabatan == 4 ? 'selected' : '' }}>(4) Kepala Bidang,
                                            Kepala Bagian, Kepala Instalasi, IPCLN</option>
                                        <option value="6"
                                            {{ $tl->jabatan == 6 ? 'selected' : '' }}>(6) Ketua Komite
                                            Medik, Ketua Komite Keperawatan, Ketua SPI, Wadir</option>
                                        <option value="8"
                                            {{ $tl->jabatan == 8 ? 'selected' : '' }}>(8) Direktur
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <select name="pegawai[{{ $tl->source_id }}][pendidikan_formal]"
                                        class="form-select form-select-sm text-center pendidikan-formal"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                        <option value="0"
                                            {{ $tl->pendidikan_formal == 0 ? 'selected' : '' }}>-- Pilih
                                            Pendidikan --</option>
                                        <option value="1"
                                            {{ $tl->pendidikan_formal == 1 ? 'selected' : '' }}>SD
                                        </option>
                                        <option value="2"
                                            {{ $tl->pendidikan_formal == 2 ? 'selected' : '' }}>SMP
                                        </option>
                                        <option value="3"
                                            {{ $tl->pendidikan_formal == 3 ? 'selected' : '' }}>SMA/SMU
                                        </option>
                                        <option value="4"
                                            {{ $tl->pendidikan_formal == 4 ? 'selected' : '' }}>D1
                                        </option>
                                        <option value="5"
                                            {{ $tl->pendidikan_formal == 5 ? 'selected' : '' }}>D3
                                        </option>
                                        <option value="6"
                                            {{ $tl->pendidikan_formal == 6 ? 'selected' : '' }}>S1/D4
                                        </option>
                                        <option value="7"
                                            {{ $tl->pendidikan_formal == 7 ? 'selected' : '' }}>Dokter
                                            Umum / Dokter Gigi / Apoteker / Ners</option>
                                        <option value="8"
                                            {{ $tl->pendidikan_formal == 8 ? 'selected' : '' }}>S2
                                        </option>
                                        <option value="9"
                                            {{ $tl->pendidikan_formal == 9 ? 'selected' : '' }}>Dokter
                                            Spesialis</option>
                                        <option value="10"
                                            {{ $tl->pendidikan_formal == 10 ? 'selected' : '' }}>S3 /
                                            Subspesialis / Konsultan</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][pendidikan_non_formal]"
                                        class="form-control form-control-sm text-center pendidikan-non-formal"
                                        value="{{ $tl->pendidikan_non_formal }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][gaji_pokok]"
                                        class="form-control form-control-sm text-center gaji-pokok"
                                        value="{{ $tl->gaji_pokok_display }}" readonly>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $tl->source_id }}][risk]"
                                        class="form-control form-control-sm text-center bg-light risk"
                                        value="{{ $tl->risk }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][emergency]"
                                        class="form-control form-control-sm text-center bg-light emergency"
                                        value="{{ $tl->emergency }}" readonly>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $tl->source_id }}][cuti]"
                                        class="form-control form-control-sm text-center cuti"
                                        value="{{ $tl->cuti }}"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $tl->source_id }}][izin]"
                                        class="form-control form-control-sm text-center izin"
                                        value="{{ $tl->izin }}"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][tanpa_izin]"
                                        class="form-control form-control-sm text-center tanpa-izin"
                                        value="{{ $tl->tanpa_izin }}"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <input type="text" name="pegawai[{{ $tl->source_id }}][telat]"
                                        class="form-control form-control-sm text-center telat"
                                        value="{{ $tl->telat }}"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                </td>

                                <td>
                                    <select name="pegawai[{{ $tl->source_id }}][sikap]"
                                        class="form-select form-select-sm text-center sikap"
                                        {{ $group->disable_input ? 'disabled' : '' }}>
                                        <option value="0" {{ $tl->sikap == 0 ? 'selected' : '' }}>
                                            -</option>
                                        <option value="1" {{ $tl->sikap == 1 ? 'selected' : '' }}>
                                            Terbukti mencuri di RS (-100%)</option>
                                        <option value="2" {{ $tl->sikap == 2 ? 'selected' : '' }}>
                                            Terbukti narkoba/miras/judi/mesum di RS (-100%)</option>
                                        <option value="3" {{ $tl->sikap == 3 ? 'selected' : '' }}>
                                            Merokok di lingkungan RS (-25%)</option>
                                        <option value="4" {{ $tl->sikap == 4 ? 'selected' : '' }}>
                                            Berkelahi di lingkungan RS (-50%)</option>
                                        <option value="5" {{ $tl->sikap == 5 ? 'selected' : '' }}>
                                            Mogok kerja/menghasut (-100%)</option>
                                    </select>
                                </td>

                                <td class="fw-bold">
                                    <input type="text" name="pegawai[{{ $tl->source_id }}][jumlah]"
                                        class="form-control form-control-sm text-center jumlah-pengurang jumlah"
                                        value="{{ $tl->jumlah }}" readonly>
                                </td>

                                <td>
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][keterangan]"
                                        class="form-control form-control-sm"
                                        value="{{ $tl->keterangan }}"
                                        {{ $group->disable_input ? 'readonly' : '' }}>
                                </td>

                                <td class="fw-bold text-primary">
                                    <input type="text"
                                        name="pegawai[{{ $tl->source_id }}][jumlah_akhir]"
                                        class="form-control form-control-sm text-center jumlah-akhir"
                                        value="{{ $tl->jumlah_akhir }}" readonly>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Catatan Revisi --}}
            @if ($group->status_pengajuan == 'revisi' && $group->catatan_revisi)
                <div class="mt-3 p-3 bg-light border rounded shadow-sm">
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-0">
                        <i data-feather="alert-triangle" class="flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Catatan Revisi dari Manajemen:</strong>
                            <p class="mb-0 text-danger">{{ $group->catatan_revisi }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary px-4 btn-draft"
                    {{ $group->disable_input ? 'disabled' : '' }}>
                    <i data-feather="save" class="me-1 small"></i>
                    Simpan Draft
                </button>

                <button type="submit" class="btn btn-success px-4 btn-submit"
                    formaction="{{ route('index.scoring.submit') }}"
                    {{ $group->disable_input ? 'disabled' : '' }}>
                    <i data-feather="check-circle" class="me-1"></i>
                    Kunci & Submit Pembagian
                </button>
            </div>

        </form>
    </div>
</div>