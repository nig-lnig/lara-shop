{{--
    Labelled form field with its validation message.
    Usage: <x-input name="city" label="Ville" :value="$client->city" required />
    old() puts back what the user typed when validation fails.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null])

<div class="field">
    <label for="{{ $name }}">{{ $label }} @if ($attributes->has('required'))<span class="required" aria-hidden="true">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            @if ($type !== 'password' && $type !== 'file') value="{{ old($name, $value) }}" @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes }}>
    @endif
    @if ($help)
        <small class="help">{{ $help }}</small>
    @endif
    @error($name)
        <small id="{{ $name }}-error" class="field-error"><x-icon name="alert" /> {{ $message }}</small>
    @enderror
</div>
