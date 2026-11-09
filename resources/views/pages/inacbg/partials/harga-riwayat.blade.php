@php
    $hargaData = $c->keterangan[$hargaSection] ?? [];
    $formatHarga = fn($v) => number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2, ',', '.');
@endphp
@if (isset($hargaData['harga_awal']))
    <div class="small text-muted mb-3">
        Harga awal: <strong>Rp {{ $formatHarga($hargaData['harga_awal']) }}</strong>
        @if (!empty($hargaData['riwayat_harga']))
            <details class="mt-1">
                <summary>Riwayat perubahan harga</summary>
                @foreach (array_reverse($hargaData['riwayat_harga']) as $perubahan)
                    <div class="mt-2">
                        Rp {{ $formatHarga($perubahan['harga_lama']) }}
                        &rarr; Rp {{ $formatHarga($perubahan['harga_baru']) }}
                        <div>{{ $perubahan['user_name'] ?? '-' }}
                            &middot; {{ \Carbon\Carbon::parse($perubahan['updated_at'])->format('d/m/Y H:i') }}</div>
                    </div>
                @endforeach
            </details>
        @endif
    </div>
@endif
