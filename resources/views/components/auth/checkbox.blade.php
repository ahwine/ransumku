@props(['id', 'name', 'value' => '1', 'required' => false, 'checked' => false, 'requiredMessage' => 'Centang kotak ini untuk melanjutkan.'])
<div class="au-field au-field-check" data-field>
    <label class="au-check" for="{{ $id }}">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            @if ($checked) checked @endif
            @if ($required) required data-required-message="{{ $requiredMessage }}" aria-describedby="{{ $id }}-error" @endif
        >
        <span>{{ $slot }}</span>
    </label>
    @if ($required)
        <p class="au-field-error" id="{{ $id }}-error" aria-live="polite" hidden>
            <span class="material-symbols-outlined au-ico icon-fill" aria-hidden="true">error</span>
            <span data-error-text></span>
        </p>
    @endif
</div>

