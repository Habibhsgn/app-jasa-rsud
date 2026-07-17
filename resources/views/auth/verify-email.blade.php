<x-guest-layout>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 50vh;">
        <div class="card shadow-sm p-4" style="width: 480px;">

            <div class="text-center mb-3">
                <h3 class="fw-bold">Verifikasi Email</h3>
                <p class="text-muted mb-2">
                    {{ __('Terima kasih sudah mendaftar! Sebelum melanjutkan, silakan verifikasi alamat email kamu dengan mengklik link yang sudah kami kirimkan.') }}
                </p>

                @auth
                    <span class="badge bg-secondary">{{ auth()->user()->email }}</span>
                @endauth
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success text-center" role="alert">
                    {{ __('Link verifikasi baru sudah dikirim ke alamat email yang kamu daftarkan.') }}
                </div>
            @endif

            <p class="text-center text-muted small mb-3">
                {{ __('Belum menerima email? Cek folder Spam/Promotions, atau klik tombol di bawah untuk mengirim ulang.') }}
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="d-grid mb-2">
                @csrf
                <button type="submit" class="btn btn-primary">
                    {{ __('Kirim Ulang Email Verifikasi') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="btn btn-link text-muted text-decoration-underline">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>