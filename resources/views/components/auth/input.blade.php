@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'inputmode' => null,
    'required' => true,
    'autofocus' => false,
    'minlength' => null,
])
@php
    $errorId = $id . '-error';
    $describedBy = trim(($hint ? $id . '-hint ' : '') . $errorId);
@endphp
<div class="au-field" data-field>
    <label class="au-label" for="{{ $id }}">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        class="au-input{{ $error ? ' is-invalid' : '' }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($minlength) minlength="{{ $minlength }}" @endif
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        aria-describedby="{{ $describedBy }}"
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        {{ $attributes }}
    >
    @if ($hint)
        <p class="au-hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    <p class="au-field-error" id="{{ $errorId }}" aria-live="polite" @unless ($error) hidden @endunless>
        <span class="material-symbols-outlined au-ico icon-fill" aria-hidden="true">error</span>
        <span data-error-text>{{ $error }}</span>
    </p>
    {{ $slot }}
</div>

