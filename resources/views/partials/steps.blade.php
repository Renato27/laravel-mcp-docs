<ol class="steps">
    @foreach ($steps as $step)
        <li class="step">
            <div class="step-body">
                <h5 class="step-title">{!! LaravelMcpDocs\Support\Text::inline($step->title) !!}</h5>
                @if ($step->body)
                    <p>{!! LaravelMcpDocs\Support\Text::inline($step->body) !!}</p>
                @endif
            </div>
            @if ($step->image)
                <figure class="step-figure">
                    <img src="{{ $step->image }}" alt="{{ $step->caption ?? $step->title }}" loading="lazy">
                    @if ($step->caption)
                        <figcaption>{{ $step->caption }}</figcaption>
                    @endif
                </figure>
            @endif
        </li>
    @endforeach
</ol>

@if ($configured ?? false)
    @if ($docsUrl)
        <p class="source">
            If a step no longer matches,
            <a href="{{ $docsUrl }}" rel="noreferrer noopener" target="_blank">the {{ $clientLabel }} documentation</a>
            is the authority.
        </p>
    @endif
@else
    <p class="source">
        Checked against
        @if ($docsUrl)
            <a href="{{ $docsUrl }}" rel="noreferrer noopener" target="_blank">the {{ $clientLabel }} documentation</a>
        @else
            the {{ $clientLabel }} documentation
        @endif
        on {{ \Illuminate\Support\Carbon::parse(LaravelMcpDocs\Install\Client::VERIFIED_ON)->isoFormat('D MMMM YYYY') }}.
        These interfaces change without notice — if a step no longer matches, the linked page is the authority.
    </p>
@endif
