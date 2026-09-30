@props([
    'heading',
    'body' => null,
    'label',
    'href',
    'secondaryLabel' => null,
    'secondaryHref' => null,
    'tone' => 'leaf',
])
<aside {{ $attributes->class(['qapas-cta-block', 'qapas-cta-block--ink' => $tone === 'ink']) }}>
    <div>
        <h2>{{ $heading }}</h2>
        @if ($body)<p>{{ $body }}</p>@endif
    </div>
    <div class="qapas-cta-block__actions">
        <a class="qapas-cta-block__primary" href="{{ $href }}">{{ $label }}</a>
        @if ($secondaryHref && $secondaryLabel)
            <a class="qapas-cta-block__secondary" href="{{ $secondaryHref }}">{{ $secondaryLabel }}</a>
        @endif
    </div>
</aside>

