@php
    $hargaConfig = \App\Models\InacbgClaim::SECTIONS[$hargaSection];
    $hargaHasError = old('harga_section') === $hargaSection && ($errors->has('harga') || $errors->has('harga_rupiah'));
    $hargaValue = old('harga_section') === $hargaSection ? old('harga') : $c->{$hargaConfig['column']};
    $hargaFormatted = is_numeric($hargaValue)
        ? number_format((float) $hargaValue, (float) $hargaValue == floor((float) $hargaValue) ? 0 : 2, ',', '.')
        : $hargaValue;
    if (old('harga_section') === $hargaSection) {
        $hargaFormatted = old('harga_rupiah', $hargaFormatted);
    }
@endphp
<div class="mb-3">
    <input type="hidden" name="harga_section" value="{{ $hargaSection }}">
    <label for="harga-{{ $c->id }}-{{ $hargaSection }}" class="form-label">Harga {{ $hargaConfig['label'] }}</label>
    <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="hidden" name="harga" value="{{ $hargaValue }}">
        <input type="text" name="harga_rupiah" id="harga-{{ $c->id }}-{{ $hargaSection }}"
            data-rupiah inputmode="decimal"
            required pattern="[0-9.,]+" placeholder="500.000" value="{{ $hargaFormatted }}"
            class="form-control text-start {{ $hargaHasError ? 'is-invalid' : '' }}">
        @if ($hargaHasError)
            <div class="invalid-feedback">{{ $errors->first('harga_rupiah') ?: $errors->first('harga') }}</div>
        @endif
    </div>
    <div class="form-text">Harga disimpan bersama keterangan. Harga awal dan riwayat perubahan tetap tersimpan.</div>
</div>
