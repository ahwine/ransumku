@props(['id', 'label', 'loadingLabel' => 'Memproses...', 'type' => 'submit'])
<button id="{{ $id }}" type="{{ $type }}" class="au-btn" data-auth-submit {{ $attributes }}>
    <span class="au-btn-label">{{ $label }}</span>
    <span class="au-btn-loading" aria-hidden="true"><span class="au-spin"></span>{{ $loadingLabel }}</span>
</button>

