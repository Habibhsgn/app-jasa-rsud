/**
 * INA-CBG — interaksi dinamis tanpa reload halaman.
 *
 * - Form ber-atribut [data-ajax] dikirim via fetch, lalu progres/tabel dimuat ulang diam-diam.
 * - Filter (bulan, tahun, status, keterangan, dokter utama, tindakan, cari) langsung diterapkan, pencarian memakai debounce.
 * - Paginasi, sorting, dan tombol filter panel dimuat ulang tanpa pindah halaman.
 * - Hasil aksi ditampilkan sebagai toast.
 */
(function () {
    'use strict';

    var PARTS = ['#inacbg-tabs', '#inacbg-progress', '#inacbg-table'];
    var seq = 0;

    // ---------------------------------------------------------------- Toast
    function toast(message, type) {
        type = type || 'success';
        var box = document.getElementById('toastBox');
        if (!box) {
            box = document.createElement('div');
            box.id = 'toastBox';
            box.className = 'toast-container position-fixed top-0 end-0 p-3';
            box.style.zIndex = 2000;
            document.body.appendChild(box);
        }

        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + type + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="d-flex"><div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button></div>';
        el.querySelector('.toast-body').textContent = message;
        box.appendChild(el);

        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        new bootstrap.Toast(el, { delay: 3000 }).show();
    }

    // ------------------------------------------------------- Soft reload
    function syncExport() {
        var ex = document.getElementById('btnExport');
        if (!ex || !ex.dataset.base) return;
        var params = new URLSearchParams(location.search);
        if (!params.has('tab') && ex.dataset.tab) params.set('tab', ex.dataset.tab);
        ex.href = ex.dataset.base + '?' + params.toString();
    }

    // Segarkan angka di label opsi filter (Keterangan, Dokter Utama, Tindakan) tanpa
    // mengganti elemen filter, supaya fokus & ketikan di kolom cari tidak hilang.
    function syncFilterCounts(doc) {
        doc.querySelectorAll('#inacbg-filter select').forEach(function (fs) {
            var old = document.querySelector('#inacbg-filter select[name="' + fs.name + '"]');
            if (!old) return;
            Array.prototype.forEach.call(fs.options, function (o) {
                var target = Array.prototype.find.call(old.options, function (x) { return x.value === o.value; });
                if (target) target.textContent = o.textContent;
            });
        });
    }

    async function softLoad(url) {
        var mine = ++seq;
        var table = document.querySelector('#inacbg-table');
        if (table) {
            table.classList.add('is-loading');
            table.setAttribute('aria-busy', 'true');
        }

        try {
            var res = await fetch(url || location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Gagal memuat data.');

            var html = await res.text();
            if (mine !== seq) return; // ada permintaan yang lebih baru

            var doc = new DOMParser().parseFromString(html, 'text/html');
            PARTS.forEach(function (sel) {
                var fresh = doc.querySelector(sel);
                var old = document.querySelector(sel);
                if (fresh && old) old.replaceWith(fresh);
            });

            syncFilterCounts(doc);
            syncExport();
            if (window.feather) window.feather.replace();
        } catch (err) {
            if (mine === seq) {
                toast('Gagal memuat ulang data.', 'danger');
                var t = document.querySelector('#inacbg-table');
                if (t) {
                    t.classList.remove('is-loading');
                    t.removeAttribute('aria-busy');
                }
            }
        }
    }

    // ------------------------------------------------ Form aksi [data-ajax]
    document.addEventListener('submit', async function (e) {
        var form = e.target.closest && e.target.closest('form[data-ajax]');
        if (!form || e.defaultPrevented) return; // defaultPrevented = user menekan Batal di confirm()
        e.preventDefault();

        var buttons = form.querySelectorAll('button[type=submit]');
        buttons.forEach(function (b) { b.disabled = true; });

        var data = new FormData(form);
        if (e.submitter && e.submitter.name) {
            data.append(e.submitter.name, e.submitter.value);
        }

        var tindakanCheckboxes = form.querySelectorAll('input[name="tindakan[]"]');
        tindakanCheckboxes.forEach(function (input) { input.disabled = true; });

        try {
            var res = await fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            var json = await res.json().catch(function () { return {}; });
            if (!res.ok) throw new Error(json.message || 'Terjadi kesalahan.');

            toast(json.message || 'Tersimpan.');
            await softLoad();
        } catch (err) {
            toast(err.message || 'Terjadi kesalahan.', 'danger');
            buttons.forEach(function (b) { b.disabled = false; });
            tindakanCheckboxes.forEach(function (input) { input.checked = input.defaultChecked; });
        } finally {
            tindakanCheckboxes.forEach(function (input) { input.disabled = false; });
        }
    });

    // ------------------------------------------------------ Filter dinamis
    // Semua field yang terlihat di form filter dibaca; tab dipertahankan dari hidden input.
    function loadFromFilter(form, replace) {
        var url = new URL(location.href);

        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || el.disabled) return;
            if (['hidden', 'submit', 'button'].indexOf(el.type) !== -1) return;
            url.searchParams.set(el.name, el.value); // nilai kosong tetap dikirim ("Semua Bulan")
        });
        url.searchParams.delete('page');

        if (replace) {
            history.replaceState(null, '', url);
        } else {
            history.pushState(null, '', url);
        }
        softLoad(url.href);
    }

    function syncFilterFromUrl() {
        var form = document.querySelector('#inacbg-filter form');
        if (!form) return;
        var params = new URLSearchParams(location.search);

        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || ['hidden', 'submit', 'button'].indexOf(el.type) !== -1) return;
            if (params.has(el.name)) el.value = params.get(el.name);
        });
    }

    var timer;
    document.addEventListener('input', function (e) {
        if (!e.target.matches || !e.target.matches('#inacbg-filter input[name=search]')) return;
        clearTimeout(timer);
        timer = setTimeout(function () { loadFromFilter(e.target.form, true); }, 450);
    });

    document.addEventListener('change', function (e) {
        if (!e.target.matches || !e.target.matches('#inacbg-filter select')) return;
        loadFromFilter(e.target.form, false);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest && e.target.closest('#inacbg-filter form');
        if (!form) return;
        e.preventDefault();
        clearTimeout(timer);
        loadFromFilter(form, false);
    });

    // ------------------------------------ Paginasi, sorting, filter panel
    document.addEventListener('click', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

        var a = e.target.closest(
            '#inacbg-table .pagination a, #inacbg-table thead a, #inacbg-progress .btn-group a'
        );
        if (!a || !a.href) return;

        e.preventDefault();
        history.pushState(null, '', a.href);
        softLoad(a.href);
    });

    window.addEventListener('popstate', function () {
        syncFilterFromUrl();
        softLoad(location.href);
    });
})();
