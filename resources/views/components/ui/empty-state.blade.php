@props([
    'message',
])

<p {{ $attributes->merge(['class' => 'empty-state']) }}>{{ $message }}</p>
