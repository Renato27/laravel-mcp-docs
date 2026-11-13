@php($label = $primitive->title() ?? $primitive->name)
@php($panel = $server->slug().'-'.$primitive->slug().'-body')

<article class="primitive" id="{{ $primitive->slug() }}">
    <header class="primitive-head">
        <div class="primitive-title">
            <h4>{{ $label }}</h4>
            <code class="name">{{ $primitive->uri() ?? $primitive->name }}</code>
        </div>
        <button type="button" class="primitive-collapse" data-group-toggle
                aria-controls="{{ $panel }}" aria-expanded="{{ $primitivesOpen ? "true" : "false" }}"
                aria-label="Collapse {{ $label }}">
            <svg viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </header>

    <div class="primitive-body" id="{{ $panel }}" @unless ($primitivesOpen) hidden @endunless>
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

        @if ($primitive->arguments())
            @php($argumentsDescribed = collect($primitive->arguments())->contains(fn (array $argument): bool => ($argument['description'] ?? '') !== ''))
            <h5>Arguments</h5>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Required</th>
                        @if ($argumentsDescribed)
                            <th scope="col">Description</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($primitive->arguments() as $argument)
                        <tr>
                            <td><code>{{ $argument['name'] ?? '' }}</code></td>
                            <td>{!! ($argument['required'] ?? false) ? '<span class="req">yes</span>' : '<span class="opt">no</span>' !!}</td>
                            @if ($argumentsDescribed)
                                <td>{{ $argument['description'] ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</article>
