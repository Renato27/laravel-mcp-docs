@php($rows = LaravelMcpDocs\Support\SchemaTable::rows($primitive->inputSchema()))
@php($outputRows = LaravelMcpDocs\Support\SchemaTable::rows($primitive->outputSchema()))
@php($label = $primitive->title() ?? $primitive->name)
@php($panel = $server->slug().'-'.$primitive->slug().'-body')

<article class="primitive" id="{{ $primitive->slug() }}">
    <header class="primitive-head">
        <div class="primitive-title">
            <h4>{{ $label }}</h4>
            <code class="name">{{ $primitive->name }}</code>
        </div>
        <button type="button" class="primitive-collapse" data-group-toggle
                aria-controls="{{ $panel }}" aria-expanded="{{ $primitivesOpen ? "true" : "false" }}"
                aria-label="Collapse {{ $label }}">
            <svg viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </header>

    <div class="primitive-body" id="{{ $panel }}" @unless ($primitivesOpen) hidden @endunless>
        @if ($primitive->badges())
            <p class="badges">
                @foreach ($primitive->badges() as $badge)
                    <span class="badge badge-{{ $badge['tone'] }}">{{ $badge['label'] }}</span>
                @endforeach
            </p>
        @endif

        @if ($primitive->docBlock->summary)
            <p class="lede">{!! LaravelMcpDocs\Support\Text::inline($primitive->docBlock->summary) !!}</p>
        @endif
        @if ($primitive->docBlock->description)
            @include('mcp-docs::partials.prose', [
                'text' => $primitive->docBlock->description,
                'id' => 'about-'.$primitive->slug(),
            ])
        @endif

        @if ($primitive->description())
            <details class="model-view">
                <summary>What the assistant is told</summary>
                <div class="model-text">{{ $primitive->description() }}</div>
            </details>
        @endif

        @if ($rows)
            <h5>Inputs</h5>
            @include('mcp-docs::partials.schema-table', ['rows' => $rows])
        @else
            <p class="note">Takes no arguments.</p>
        @endif

        @if ($outputRows)
            <h5>Returns</h5>
            @include('mcp-docs::partials.schema-table', ['rows' => $outputRows])
        @endif
    </div>
</article>
