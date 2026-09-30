@props([
    'title',
    'badge' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => null,
    'video' => null,
    'prices' => [],
    'primaryLabel' => null,
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])
<article {{ $attributes->class(['qapas-listing-tile']) }}>
    @if ($video)
        <video class="qapas-listing-tile__media" controls preload="none" @if ($image) poster="{{ $image }}" @endif aria-label="{{ $imageAlt ?: $title }}">
            <source src="{{ $video }}">
        </video>
    @elseif ($image)
        <img class="qapas-listing-tile__media" src="{{ $image }}" alt="{{ $imageAlt ?: $title }}" loading="lazy" decoding="async">
    @endif
    <div class="qapas-listing-tile__content">
        @if ($badge)<span class="qapas-listing-tile__badge">{{ $badge }}</span>@endif
        <h2>{{ $title }}</h2>
        @if ($description)<p>{{ $description }}</p>@endif
        @if (count($prices))
            <dl class="qapas-listing-tile__prices">
                @foreach ($prices as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
            </dl>
        @endif
        @if ($primaryUrl || $secondaryUrl)
            <div class="qapas-listing-tile__actions">
                @if ($primaryUrl && $primaryLabel)<a class="qapas-listing-tile__primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>@endif
                @if ($secondaryUrl && $secondaryLabel)<a class="qapas-listing-tile__secondary" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>@endif
            </div>
        @endif
    </div>
</article>

