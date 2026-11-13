@if ($snippet)
    @if ($snippet->path)
        <p class="path">Add this to <code>{{ $snippet->path }}</code></p>
    @endif
    <div class="snippet">
        <button type="button" class="copy" data-copy>Copy</button>
        <pre><code>{{ $snippet->code }}</code></pre>
    </div>
    @foreach ($snippet->notes as $note)
        <p class="note">{!! LaravelMcpDocs\Support\Text::inline($note) !!}</p>
    @endforeach
@endif
