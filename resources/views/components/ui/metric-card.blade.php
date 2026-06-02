@props([
    'label',
    'value',
    'hint' => null,
])

<article {{ $attributes->merge(['class' => 'metric-card']) }}>
    <span class="metric-card-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
            <path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-5H4v5Z" />
        </svg>
    </span>
    <span>{{ $label }}</span>
    <strong>{{ $value }}</strong>
    @if ($hint)
        <small>{{ $hint }}</small>
    @endif
</article>
