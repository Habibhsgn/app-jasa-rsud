<x-guest-layout>
    <div class="text-center mb-4">
        <h3 class="fw-bold">Login</h3>
        <p class="text-muted">Silakan login untuk melanjutkan</p>
    </div>

    <x-auth-session-status class="alert alert-success" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label>Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label>Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" required>
            </div>
            @error('password')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input">
            <label class="form-check-label">Remember me</label>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-modern">Login</button>
        </div>

        <div class="text-center">
            <a href="{{ route('password.request') }}">Lupa Password?</a>
        </div>
        <div class="text-center">
            <a href="{{ route('register') }}">Belum Memiliki akun?</a>
        </div>
    </form>
</x-guest-layout>