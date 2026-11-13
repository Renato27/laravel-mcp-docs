<section id="{{ $server->slug() }}" class="server">
    <header class="server-head">
        <div>
            <h2>{{ $server->name }}</h2>
            <p class="meta">
                <span class="badge badge-neutral">v{{ $server->version }}</span>
                <span class="badge badge-neutral">{{ $server->transport()->label() }}</span>
            </p>
        </div>
        <code class="endpoint" title="{{ $server->endpoint() }}">{{ $server->endpoint() }}</code>
    </header>

    @if ($server->docBlock->summary)
        <p class="lede">{!! LaravelMcpDocs\Support\Text::inline($server->docBlock->summary) !!}</p>
    @endif
    @if ($server->docBlock->description)
        @include('mcp-docs::partials.prose', [
            'text' => $server->docBlock->description,
            'id' => $server->slug().'-about',
        ])
    @endif

    @if ($server->instructions)
        <div class="callout">
            <h4>Instructions given to the assistant</h4>
            @include('mcp-docs::partials.prose', [
                'text' => $server->instructions,
                'id' => $server->slug().'-instructions',
            ])
        </div>
    @endif

    @foreach ($server->warnings as $warning)
        <p class="alert" role="status">{{ $warning }}</p>
    @endforeach

    <h3 id="{{ $server->slug() }}-install">Connect a client</h3>
    <p class="section-lede">Pick your client, then either paste the configuration or follow the steps through its settings.</p>
    <div class="tabs" data-tabs>
        <div class="tablist" role="tablist" aria-label="Clients for {{ $server->name }}">
            @foreach ($clients as $i => $client)
                <button type="button" role="tab"
                        id="tab-{{ $server->slug() }}-{{ $client->key() }}"
                        aria-controls="panel-{{ $server->slug() }}-{{ $client->key() }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        tabindex="{{ $i === 0 ? '0' : '-1' }}">{{ $client->label() }}</button>
            @endforeach
        </div>
        @foreach ($clients as $i => $client)
            <div role="tabpanel" class="tabpanel"
                 id="panel-{{ $server->slug() }}-{{ $client->key() }}"
                 aria-labelledby="tab-{{ $server->slug() }}-{{ $client->key() }}"
                 @if ($i !== 0) hidden @endif>
                @include('mcp-docs::partials.client', [
                    'walkthroughs' => $walkthroughs[$client->key()] ?? [],
                    'id' => $server->slug().'-'.$client->key(),
                    'docsUrl' => $client->docsUrl($server),
                    'clientLabel' => $client->label(),
                    'video' => $client->video(),
                ])
            </div>
        @endforeach
    </div>

    @if ($server->tools)
        <h3 id="{{ $server->slug() }}-tools">Tools</h3>
        <p class="section-lede">
            Actions the assistant can take.
            @if (collect($server->tools)->contains(fn ($tool): bool => $tool->description() !== null))
                It chooses which to call based on the description.
            @else
                It chooses which to call from what you ask.
            @endif
        </p>
        @foreach ($server->tools as $tool)
            @include('mcp-docs::partials.tool', ['primitive' => $tool, 'server' => $server])
        @endforeach
    @endif

    @if ($server->resources || $server->resourceTemplates)
        <h3 id="{{ $server->slug() }}-resources">Resources</h3>
        <p class="section-lede">Read-only content the assistant can pull in as context.</p>
        @foreach (array_merge($server->resources, $server->resourceTemplates) as $resource)
            @include('mcp-docs::partials.primitive', ['primitive' => $resource, 'server' => $server])
        @endforeach
    @endif

    @if ($server->prompts)
        <h3 id="{{ $server->slug() }}-prompts">Prompts</h3>
        <p class="section-lede">Prepared instructions a user can invoke by name.</p>
        @foreach ($server->prompts as $prompt)
            @include('mcp-docs::partials.primitive', ['primitive' => $prompt, 'server' => $server])
        @endforeach
    @endif
</section>
