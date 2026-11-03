@props(['size' => 56, 'wordmark' => true])
<div {{ $attributes->except('style')->merge(['class' => 'au-brand-row']) }} style="--au-logo-size: {{ $size }}px; {{ $attributes->get('style') }}">
    <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="au-logo" width="{{ $size }}" height="{{ $size }}">
    @if ($wordmark)
        <span class="au-wordmark">RansumKu</span>
    @endif
</div>
