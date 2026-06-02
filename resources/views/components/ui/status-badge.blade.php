@props([
    'status',
    'label' => null,
])

@php
    $normalized = strtolower(str_replace([' ', '_'], '-', (string) $status));
@endphp

<span {{ $attributes->merge(['class' => 'status-badge status-'.$normalized]) }}>
    {{ $label ?? ucfirst(str_replace(['_', '-'], ' ', (string) $status)) }}
</span>
