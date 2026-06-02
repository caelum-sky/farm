@props([
    'href',
    'label',
    'description',
])

<a {{ $attributes->merge(['class' => 'action-card']) }} href="{{ $href }}">
    <span class="action-card-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
            <path d="M5 12h12.17l-5.58-5.59L13 5l8 8-8 8-1.41-1.41L17.17 14H5v-2Z" />
        </svg>
    </span>
    <span>{{ $label }}</span>
    <strong>{{ $description }}</strong>
</a>
