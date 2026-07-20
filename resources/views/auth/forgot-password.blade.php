<x-guest-layout>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 50vh;">
        <div class="card shadow-sm p-4" style="width: 400px;">

            <div class="text-center mb-4">
                <h3 class="fw-bold">Lupa Password</h3>
                <p class="text-muted">
                    {{ __('Masukkan alamat email kamu, kami akan mengirimkan link untuk reset password.') }}
                </p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="alert alert-success text-center">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input id="email" type="email" name="email" class="form-control"
                               placeholder="Masukkan email" value="{{ old('email') }}" required autofocus>
                    </div>
                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Kirim Link Reset Password') }}
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-decoration-none small">Kembali ke Login</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>