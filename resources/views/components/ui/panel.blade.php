@props([
    'title' => null,
    'eyebrow' => null,
    'actionHref' => null,
    'actionLabel' => null,
])

<section {{ $attributes->merge(['class' => 'ui-panel']) }}>
    @if ($title || $eyebrow || $actionHref)
        <div class="panel-heading">
            <div>
                @if ($eyebrow)
                    <span class="eyebrow">{{ $eyebrow }}</span>
                @endif
                @if ($title)
                    <h2>{{ $title }}</h2>
                @endif
            </div>
            @if ($actionHref && $actionLabel)
                <a href="{{ $actionHref }}">{{ $actionLabel }}</a>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
