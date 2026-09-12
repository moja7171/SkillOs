@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-ok']) }}>
        {{ $status }}
    </div>
@endif
