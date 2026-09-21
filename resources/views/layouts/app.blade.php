<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    {{-- Bootstrap CSS (CDN) - pelengkap komponen yg tidak ter-compile di app.css --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- ============================================================
         Perbaikan layout: sidebar & konten punya scroll masing-masing.
         Ditaruh SETELAH semua CSS supaya menimpa aturan bawaan template.
         ============================================================ --}}
    <style>
        /* Tinggi aplikasi = tinggi layar; window sendiri tidak ikut scroll */
        .wrapper {
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }

        /* Sidebar: scroll sendiri */
        .sidebar {
            height: 100vh;
            height: 100dvh;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            flex-shrink: 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, .25) transparent;
        }

        .sidebar .sidebar-content {
            height: auto;
            min-height: 100%;
        }

        /* Konten: scroll sendiri */
        .main {
            height: 100vh;
            height: 100dvh;
            min-height: 0;
            min-width: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Navbar tetap terlihat di atas saat konten di-scroll */
        .main>.navbar {
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .sidebar-backdrop {
            display: none;
        }

        /* Desktop: collapse via hamburger */
        @media (min-width: 992px) {
            .sidebar.collapsed {
                margin-left: -260px;
            }
        }

        /* Mobile: sidebar jadi panel yang menimpa konten */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                margin-left: 0 !important;
                transform: translateX(-100%);
                transition: transform .25s ease;
                z-index: 1045;
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
                box-shadow: 0 0 24px rgba(0, 0, 0, .35);
            }

            .main {
                width: 100%;
                min-width: 0;
            }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .45);
                z-index: 1040;
            }

            body.sidebar-open .sidebar-backdrop {
                display: block;
            }
        }

        /* Cetak: jangan terpotong oleh tinggi layar */
        @media print {

            .wrapper,
            .main {
                height: auto;
                overflow: visible;
            }

            .sidebar,
            .main>.navbar {
                display: none !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="wrapper">

        {{-- Sidebar --}}
        @include('components.sidebar')

        <div class="main">

            {{-- Navbar --}}
            @include('components.navbar')

            {{-- Content --}}
            <main class="content">
                <div class="container-fluid p-0">
                    @yield('content')
                </div>
            </main>

            {{-- Footer --}}
            @include('components.footer')

        </div>
    </div>

    {{-- Latar gelap di belakang sidebar (mobile) --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    @yield('modals')

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    {{-- Scripts --}}
    @include('components.script')

    {{-- Bootstrap JS Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    {{-- Hamburger / sidebar toggle --}}
    <script>
        (function() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar || !backdrop) return;

            const mobile = window.matchMedia('(max-width: 991.98px)');

            const setMobileOpen = (open) => {
                document.body.classList.toggle('sidebar-open', open);
            };

            // Fase capture + stopPropagation: pasti hanya 1x toggle,
            // walau ada handler lain (mis. app.js bawaan template) yang juga terpasang.
            document.addEventListener('click', function(e) {
                const toggle = e.target.closest('.js-sidebar-toggle');
                if (!toggle) return;

                e.preventDefault();
                e.stopPropagation();

                if (mobile.matches) {
                    setMobileOpen(!document.body.classList.contains('sidebar-open'));
                } else {
                    sidebar.classList.toggle('collapsed');
                    // beri tahu chart/tabel agar menyesuaikan lebar
                    setTimeout(() => window.dispatchEvent(new Event('resize')), 350);
                }
            }, true);

            backdrop.addEventListener('click', () => setMobileOpen(false));

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') setMobileOpen(false);
            });

            // Tutup otomatis setelah memilih menu (mobile)
            sidebar.addEventListener('click', (e) => {
                if (mobile.matches && e.target.closest('a.sidebar-link')) setMobileOpen(false);
            });

            // Pindah ke ukuran desktop: pastikan panel mobile tertutup
            mobile.addEventListener('change', () => setMobileOpen(false));
        })();
    </script>

    <!-- GLOBAL MINI MODAL -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <script>
        function showToast(type, message) {
            const container = document.getElementById('toast-container');

            const toast = document.createElement('div');

            let bg = (type === 'success') ? '#28a745' : '#dc3545';

            toast.style.background = bg;
            toast.style.color = '#fff';
            toast.style.padding = '12px 16px';
            toast.style.marginBottom = '10px';
            toast.style.borderRadius = '8px';
            toast.style.boxShadow = '0 4px 10px rgba(0,0,0,0.15)';
            toast.style.minWidth = '250px';
            toast.style.fontSize = '14px';
            toast.style.opacity = '0';
            toast.style.transition = 'all 0.3s ease';

            toast.innerText = message;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 100);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>

    @stack('scripts')

    {{-- Flash message -> toast (@json supaya aman dari tanda kutip/newline) --}}
    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast('success', @json(session('success')));
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast('error', @json(session('error')));
            });
        </script>
    @endif
</body>

</html>
