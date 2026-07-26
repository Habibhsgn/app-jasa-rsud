@extends('layouts.app')
@section('title', 'Kelola Override: ' . $user->name)
@section('content')
    <h1 class="h3 mb-3">
        <strong>Override Permission:</strong> {{ $user->name }}
    </h1>
    <div class="container-fluid p-0">

        @include('admin.rbac.partials.nav')

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <p class="text-muted">
            Role user ini: <strong>{{ $user->role?->name ?? '-' }}</strong> ({{ $user->role?->code ?? '-' }}).
            <br>
            <strong>Ikut role</strong> = akses ditentukan normal dari role di atas.
            <strong>Grant</strong> = user ini SELALU dapat akses ini walau role-nya tidak.
            <strong>Revoke</strong> = user ini SELALU tidak dapat akses ini walau role-nya boleh (berlaku juga untuk admin).
        </p>

        <form action="{{ route('rbac.overrides.update', $user) }}" method="POST">
            @csrf @method('PUT')

            <div class="card">
                <div class="card-body">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Permission</th>
                                <th>Dari Role?</th>
                                <th style="width:340px">Status untuk user ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $permission)
                                @php
                                    $currentOverride = $overrides->get($permission->id);
                                    $currentState = $currentOverride?->type ?? 'inherit';
                                    $hasFromRole = in_array($permission->id, $rolePermissionIds) || $user->role?->isSuper();
                                @endphp
                                <tr>
                                    <td>
                                        {{ $permission->name }}
                                        <br><small class="text-muted">{{ $permission->code }}</small>
                                        @if ($permission->module)
                                            <span class="badge bg-light text-dark border">{{ $permission->module->name }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($hasFromRole)
                                            <span class="badge bg-success">Ya</span>
                                        @else
                                            <span class="badge bg-secondary">Tidak</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="state[{{ $permission->id }}]" value="inherit"
                                                id="inherit-{{ $permission->id }}" {{ $currentState === 'inherit' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-secondary btn-sm" for="inherit-{{ $permission->id }}">Ikut Role</label>

                                            <input type="radio" class="btn-check" name="state[{{ $permission->id }}]" value="grant"
                                                id="grant-{{ $permission->id }}" {{ $currentState === 'grant' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success btn-sm" for="grant-{{ $permission->id }}">Grant</label>

                                            <input type="radio" class="btn-check" name="state[{{ $permission->id }}]" value="revoke"
                                                id="revoke-{{ $permission->id }}" {{ $currentState === 'revoke' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-danger btn-sm" for="revoke-{{ $permission->id }}">Revoke</label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('rbac.overrides.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>
    </div>
@endsection
