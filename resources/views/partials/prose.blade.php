@php
    $long = LaravelMcpDocs\Support\Markdown::isLong($text);
    $rendered = LaravelMcpDocs\Support\Markdown::render($text);
@endphp

@if ((string) $rendered !== '')
    <div class="prose-block{{ $long ? ' is-collapsible' : '' }}" @if ($long) data-collapsible @endif>
        <div class="prose-body"{!! $long ? ' id="'.$id.'"' : '' !!}>{!! $rendered !!}</div>
        @if ($long)
            <button type="button" class="prose-more" data-prose-toggle
                    aria-controls="{{ $id }}" aria-expanded="false">
                <span class="prose-more-label">Show all</span>
                <svg viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        @endif
    </div>
@endif
