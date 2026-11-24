{{--
    Password input with a show / hide button and, optionally, the live list of rules.
    Usage: <x-password-field name="password" label="Mot de passe" checklist />
    The rules shown here are the same as the server rules (AppServiceProvider, Password::defaults()).
--}}
@props(['name', 'label', 'autocomplete' => 'new-password', 'checklist' => false])

@php($describedBy = trim(($checklist ? $name.'-rules ' : '').($errors->has($name) ? $name.'-error' : '')))

<div class="field">
    <label for="{{ $name }}">{{ $label }} <span class="required" aria-hidden="true">*</span></label>
    <div class="password-wrap">
        <input id="{{ $name }}" name="{{ $name }}" type="password" autocomplete="{{ $autocomplete }}" required
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @error($name) aria-invalid="true" @enderror {{ $attributes }}>
        <button type="button" class="password-toggle" data-password-toggle aria-controls="{{ $name }}"
                aria-pressed="false" aria-label="Afficher le mot de passe">
            <x-icon name="eye" class="icon eye-show" /><x-icon name="eye-off" class="icon eye-hide" />
        </button>
    </div>

    @if ($checklist)
        <ul class="password-rules" id="{{ $name }}-rules" data-password-rules="{{ $name }}" aria-label="Le mot de passe doit contenir">
            <li data-rule="length" data-min="{{ config('shop.password_min_length') }}">Au moins {{ config('shop.password_min_length') }} caractères</li>
            <li data-rule="case">Une majuscule et une minuscule</li>
            <li data-rule="digit">Un chiffre</li>
            <li data-rule="symbol">Un symbole (! ? @ # …)</li>
        </ul>
    @endif

    @if ($errors->has($name))
        {{-- All broken rules, not only the first one --}}
        <small id="{{ $name }}-error" class="field-error"><x-icon name="alert" /> {{ implode(' ', $errors->get($name)) }}</small>
    @endif
</div>
