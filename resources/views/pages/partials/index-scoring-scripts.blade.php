{{-- Script Kalkulasi Index Scoring + Validasi Submit --}}
<script>
    function getPengurangSikap(kode) {
        switch (parseInt(kode)) {
            case 1:
                return 100;
            case 2:
                return 100;
            case 3:
                return 25;
            case 4:
                return 50;
            case 5:
                return 100;
            default:
                return 0;
        }
    }

    function toNumber(value) {
        if (value === null || value === undefined) return 0;
        value = value.toString().replace(/\./g, '');
        value = value.replace(/,/g, '.');
        return parseFloat(value) || 0;
    }

    function round2(number) {
        return Math.round(number * 100) / 100;
    }

    function hitungBaris(row) {
        let jabatan = toNumber(row.querySelector('.jabatan').value);
        let pendidikanFormal = toNumber(row.querySelector('.pendidikan-formal').value);
        let pendidikanNonFormal = toNumber(row.querySelector('.pendidikan-non-formal').value);
        let gajiPokok = toNumber(row.querySelector('.gaji-pokok').value);
        let risk = toNumber(row.querySelector('.risk').value);
        let emergency = toNumber(row.querySelector('.emergency').value);
        let cuti = toNumber(row.querySelector('.cuti').value);
        let izin = toNumber(row.querySelector('.izin').value);
        let tanpaIzin = toNumber(row.querySelector('.tanpa-izin').value);
        let telat = toNumber(row.querySelector('.telat').value);
        let kodeSikap = toNumber(row.querySelector('.sikap').value);

        // ============================================================
        // TOTAL SCORE (Basic + Competency + Risk + Emergency + Position + Performance)
        // ============================================================
        let basicIndex = gajiPokok / 100000;
        let scoreBasic = basicIndex * 1;

        let competency = pendidikanFormal + pendidikanNonFormal;
        let scoreCompetency = competency * 3;

        let scoreRisk = risk * 3;
        let scoreEmergency = emergency * 3;
        let scorePosition = jabatan * 3;

        let performanceIndex = basicIndex * 2;
        let scorePerformance = performanceIndex * 4;

        let totalScore =
            scoreBasic + scoreCompetency + scoreRisk + scoreEmergency + scorePosition + scorePerformance;

        // ============================================================
        // TOTAL PERSEN PENGURANG (Cuti+Izin, Tanpa Izin, Telat, Sikap)
        // ============================================================
        let persenPengurang = 0;

        // Cuti + Izin digabung, mengikuti tabel rentang hari
        let totalCutiIzin = cuti + izin;
        let pengurangCutiIzin = 0;
        if (totalCutiIzin > 0) {
            if (totalCutiIzin < 4) {
                pengurangCutiIzin = 8;
            } else if (totalCutiIzin <= 8) {
                pengurangCutiIzin = 12;
            } else if (totalCutiIzin <= 12) {
                pengurangCutiIzin = 30;
            } else if (totalCutiIzin <= 22) {
                pengurangCutiIzin = 50;
            } else {
                pengurangCutiIzin = 100;
            }
        }
        persenPengurang += pengurangCutiIzin;

        // Tanpa Izin, mengikuti tabel rentang hari
        let pengurangTanpaIzin = 0;
        if (tanpaIzin >= 1 && tanpaIzin <= 3) pengurangTanpaIzin = 20;
        else if (tanpaIzin >= 4 && tanpaIzin <= 6) pengurangTanpaIzin = 40;
        else if (tanpaIzin >= 7 && tanpaIzin < 30) pengurangTanpaIzin = Math.ceil(tanpaIzin / 7) * 40;
        else if (tanpaIzin >= 30) pengurangTanpaIzin = 100;
        persenPengurang += pengurangTanpaIzin;

        // Telat & cepat pulang, -3% per kelipatan 7 jam
        let pengurangTelat = Math.floor(telat / 7) * 3;
        persenPengurang += pengurangTelat;

        // Sikap, sesuai kode pelanggaran yang dipilih
        let pengurangSikap = getPengurangSikap(kodeSikap);
        persenPengurang += pengurangSikap;

        // Total pengurang di-cap maksimal 100%
        persenPengurang = Math.min(persenPengurang, 100);

        // ============================================================
        // JUMLAH AKHIR = Total Score dikurangi persen pengurang
        // ============================================================
        let jumlahAkhir = totalScore * (1 - persenPengurang / 100);

        // ============================================================
        // OUTPUT
        // ============================================================
        // FIX: kolom JUMLAH sekarang benar-benar berisi
        // "Total Skor Pengurangan" (persenPengurang), BUKAN totalScore.
        row.querySelector('.jumlah').value = round2(persenPengurang);

        row.querySelector('.jumlah-akhir').value = round2(jumlahAkhir);
    }

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('tbody tr').forEach(function(row) {
            row.querySelectorAll(
                '.jabatan, .pendidikan-formal, .cuti, .izin, .tanpa-izin, .telat, .sikap'
            ).forEach(function(input) {
                input.addEventListener('change', function() {
                    hitungBaris(row);
                });
            });
            hitungBaris(row);
        });

        document.querySelectorAll('form').forEach(function(form) {

            const btnSubmit = form.querySelector('.btn-submit');
            if (!btnSubmit) return;

            btnSubmit.addEventListener('click', function(e) {

                const belumLengkap = [];

                form.querySelectorAll('tbody tr').forEach(function(row) {

                    const jabatanSelect = row.querySelector('.jabatan');
                    const formalSelect = row.querySelector('.pendidikan-formal');

                    if (!jabatanSelect || jabatanSelect.disabled) return;

                    const namaEl = row.querySelector('td:nth-child(2) strong');
                    const nama = namaEl ? namaEl.textContent.trim() : 'Pegawai';

                    if (jabatanSelect.value === '0' || formalSelect.value === '0') {
                        belumLengkap.push(nama);
                    }
                });

                if (belumLengkap.length > 0) {
                    e.preventDefault();
                    alert(
                        'Tidak bisa submit final. Lengkapi dulu Jabatan & Pendidikan Formal untuk:\n- ' +
                        belumLengkap.join('\n- ')
                    );
                    return false;
                }

                const konfirmasi = confirm(
                    'Setelah disimpan final, data TIDAK BISA DIEDIT kembali \n\n' +
                    'Pastikan seluruh data sudah benar. Lanjutkan submit final?'
                );

                if (!konfirmasi) {
                    e.preventDefault();
                    return false;
                }
            });
        });

    });
</script>
