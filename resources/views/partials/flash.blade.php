{{--
    Page messages stored in the session with ->with('success', ...) / ->with('error', ...).
    Validation errors: each message is shown NEXT TO ITS FIELD (see components/input).
    This box is only a short summary with links that jump to the fields.
--}}
@if (session('success'))
    <div class="alert alert-success" role="status"><x-icon name="check" /> <span>{{ session('success') }}</span></div>
@endif

@if (session('error'))
    <div class="alert alert-error" role="alert"><x-icon name="alert" /> <span>{{ session('error') }}</span></div>
@endif

@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <x-icon name="alert" />
        <div>
            @php($fieldCount = count($errors->keys()))
            <strong>{{ $fieldCount > 1 ? 'Merci de corriger les '.$fieldCount.' champs signalés.' : 'Merci de corriger le champ signalé.' }}</strong>
            <ul>
                @foreach ($errors->keys() as $field)
                    <li><a href="#{{ $field }}">{{ $errors->first($field) }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
