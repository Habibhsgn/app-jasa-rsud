<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
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
    @yield('modals')

    {{-- Scripts --}}
    @include('components.script')


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

            // fade in
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 100);

            // auto remove
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            showToast('success', "{{ session('success') }}");
        });
    </script>
@endif

@if (session('error'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            showToast('error', "{{ session('error') }}");
        });
    </script>
@endif

</html>
