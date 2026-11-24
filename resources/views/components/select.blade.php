{{--
    Labelled <select> with its validation message.
    Usage: <x-select name="type" label="Type client" :options="App\Models\Client::TYPES" :value="$client->type" />
    $options is an array value => label.
--}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null])

<div class="field">
    <label for="{{ $name }}">{{ $label }} @if ($attributes->has('required'))<span class="required" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror {{ $attributes }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <small id="{{ $name }}-error" class="field-error"><x-icon name="alert" /> {{ $message }}</small>
    @enderror
</div>
