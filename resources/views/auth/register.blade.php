<x-guest-layout>
    <div class="text-center mb-4">
        <h3 class="fw-bold">Register</h3>
        <p class="text-muted">Buat akun baru</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label>Nama</label>
            <input type="text" name="name" class="form-control" required>
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label>Penempatan Ruangan</label>
            <select name="ruangan_id" class="form-control" required>
                <option value="">-- Silakan Pilih Ruangan --</option>
                @forelse($ruanganTersedia as $ruangan)
                    <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}</option>
                @empty
                    <option disabled>Semua ruangan sudah memiliki Kepala Ruangan</option>
                @endforelse
            </select>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-success btn-modern">Register</button>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}">Sudah punya akun? Login</a>
        </div>
    </form>
</x-guest-layout>