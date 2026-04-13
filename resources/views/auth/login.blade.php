<x-guest-layout>
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .login-card {
            border-radius: 20px;
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: white;
        }

        .form-control {
            border-radius: 10px;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
        }

        .form-control {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .btn-modern {
            border-radius: 12px;
            padding: 10px;
            font-weight: bold;
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            border: none;
            transition: 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .link {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
        }

        .link:hover {
            text-decoration: underline;
        }
    </style>

    <div class="d-flex justify-content-center align-items-center" style="min-height: 50vh;">
        <div class="card login-card p-4" style="width: 400px;">

            <div class="text-center mb-4">
                <h2 class="fw-bold">Welcome Back 👋</h2>
                <p class="text-light">Login untuk melanjutkan</p>
            </div>

            <x-auth-session-status class="alert alert-success" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label>Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan email" value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        <small class="text-warning">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label>Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    @error('password')
                        <small class="text-warning">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-modern">Login</button>
                </div>

                <div class="text-center">
                    <a class="link" href="{{ route('password.request') }}">Lupa Password?</a>
                </div>
                <div class="text-center mt-2">
                    <a class="link" href="{{ route('register') }}">Belum punya akun? Daftar</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
