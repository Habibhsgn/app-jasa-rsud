{{-- Pilihan tindakan radiologi. Butuh variabel $c (klaim). --}}
@php $tind = $c->radiologiTindakanList(); @endphp
<form action="{{ route('inacbg.update-tindakan', $c) }}" method="POST" class="mb-3">
    @csrf @method('PUT')
    <label class="form-label fw-semibold d-block">
        Tindakan Radiologi <span class="text-danger">*</span>
    </label>
    <div class="btn-group" role="group" aria-label="Tindakan radiologi">
        @foreach (\App\Models\InacbgClaim::TINDAKAN_RADIOLOGI as $k => $label)
            <input type="checkbox" class="btn-check" name="tindakan[]" value="{{ $k }}"
                id="tindakan-{{ $c->id }}-{{ $k }}" autocomplete="off"
                onchange="if (this.form.querySelector('input[type=checkbox]:checked')) this.form.requestSubmit(); else this.checked = true;"
                {{ in_array($k, $tind, true) ? 'checked' : '' }}>
            <label class="btn btn-outline-primary" for="tindakan-{{ $c->id }}-{{ $k }}">{{ $label }}</label>
        @endforeach
    </div>
    <div class="form-text">
        Klik Rontgen, USG, atau keduanya. Pilihan langsung tersimpan; minimal satu tindakan dipilih.
    </div>
    @error('tindakan')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</form>
