@props([
    'id',
    'name',
    'label',
    'autocomplete' => 'current-password',
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => true,
    'minlength' => null,
    'toggleId' => null,
])
@php
    $errorId = $id . '-error';
    $describedBy = trim(($hint ? $id . '-hint ' : '') . $errorId);
@endphp
<div class="au-field" data-field>
    <label class="au-label" for="{{ $id }}">{{ $label }}</label>
    <div class="au-input-wrap">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            class="au-input has-toggle{{ $error ? ' is-invalid' : '' }}"
            autocomplete="{{ $autocomplete }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($minlength) minlength="{{ $minlength }}" @endif
            @if ($required) required @endif
            aria-describedby="{{ $describedBy }}"
            aria-invalid="{{ $error ? 'true' : 'false' }}"
            {{ $attributes }}
        >
        {{-- Jika $toggleId diisi, perilaku tombol ditangani skrip halaman (login.js memakai #toggle-password). --}}
        <button type="button" class="au-eye"
            @if ($toggleId) id="{{ $toggleId }}" @else data-toggle-password="{{ $id }}" @endif
            aria-label="Tampilkan kata sandi" aria-pressed="false">
            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
        </button>
    </div>
    @if ($hint)
        <p class="au-hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    <p class="au-field-error" id="{{ $errorId }}" aria-live="polite" @unless ($error) hidden @endunless>
        <span class="material-symbols-outlined au-ico icon-fill" aria-hidden="true">error</span>
        <span data-error-text>{{ $error }}</span>
    </p>
    {{ $slot }}
</div>

