@if ($walkthrough->unavailable)
    <p class="alert" role="status">{!! LaravelMcpDocs\Support\Text::inline($walkthrough->unavailable) !!}</p>
@elseif ($walkthrough->steps)
    <div class="modes" data-tabs>
        <div class="tablist tablist-sub" role="tablist" aria-label="Setup method for {{ $clientLabel }}">
            <button type="button" role="tab"
                    id="tab-{{ $id }}-file"
                    aria-controls="panel-{{ $id }}-file"
                    aria-selected="true" tabindex="0">Config file</button>
            <button type="button" role="tab"
                    id="tab-{{ $id }}-ui"
                    aria-controls="panel-{{ $id }}-ui"
                    aria-selected="false" tabindex="-1">Step by step</button>
        </div>

        <div role="tabpanel" class="tabpanel"
             id="panel-{{ $id }}-file"
             aria-labelledby="tab-{{ $id }}-file">
            @include('mcp-docs::partials.snippet', ['snippet' => $walkthrough->snippet])
        </div>

        <div role="tabpanel" class="tabpanel"
             id="panel-{{ $id }}-ui"
             aria-labelledby="tab-{{ $id }}-ui" hidden>
            @include('mcp-docs::partials.steps', [
                'steps' => $walkthrough->steps,
                'docsUrl' => $docsUrl,
                'configured' => $walkthrough->configuredSteps,
                'clientLabel' => $clientLabel,
            ])
        </div>
    </div>
@else
    @include('mcp-docs::partials.snippet', ['snippet' => $walkthrough->snippet])
@endif
