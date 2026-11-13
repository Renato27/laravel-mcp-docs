<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if ($theme !== 'system') data-theme="{{ $theme }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    @if ($branding->favicon())
        <link rel="icon" href="{{ $branding->favicon() }}">
    @endif
    <style>{!! $css !!}</style>
    <style>{!! $branding->paletteCss() !!}</style>
    @if ($switcher)
        {{-- Applied before first paint so a stored choice never flashes the wrong theme. --}}
        <script>
            try {
                var t = localStorage.getItem('mcp-docs-theme');
                if (t === 'dark' || t === 'light') { document.documentElement.setAttribute('data-theme', t); }
            } catch (e) {}
        </script>
    @endif
</head>
<body>
<a class="skip" href="#content">Skip to content</a>

<aside class="sidebar" id="sidebar">
    <button type="button" class="sidebar-close" data-sidebar-toggle
            aria-controls="sidebar" aria-expanded="true"
            aria-label="Collapse navigation" title="Collapse navigation">
        <svg viewBox="0 0 14 14" aria-hidden="true" focusable="false"><path d="M9 2.5 4.5 7 9 11.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div class="brand"{{ $branding->hasDarkLogo() ? ' data-dual-logo' : '' }}>
        @if ($branding->logo())
            <span data-logo-theme="light">{!! $branding->logo('light') !!}</span>
            @if ($branding->hasDarkLogo())
                <span data-logo-theme="dark">{!! $branding->logo('dark') !!}</span>
            @endif
        @else
            {{-- Without a logo the collapsed rail would show an empty brand slot. --}}
            <span class="brand-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($branding->name(), 0, 1)) }}</span>
        @endif
        <span class="brand-text">
            <span class="brand-name">{{ $branding->name() }}</span>
            @if ($branding->tagline())
                <span class="brand-tagline">{{ $branding->tagline() }}</span>
            @endif
        </span>
    </div>

    @if ($branding->links())
        <div class="brand-links">
            @foreach ($branding->links() as $label => $url)
                @if (preg_match('/^https?:\/\//', $url))
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>
                @else
                    <a href="mailto:{{ $url }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>
    @endif

    <nav aria-label="Documentation">
        <a class="nav-lead" href="#what-is-mcp" title="{{ $intro->heading($title) }}">
            <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'info'])</span>
            <span class="nav-label">{{ $intro->heading($title) }}</span>
        </a>
        @foreach ($document->servers as $server)
            <div class="nav-group">
                <div class="nav-server-row">
                    <a class="nav-server" href="#{{ $server->slug() }}"
                       title="{{ $server->name }} — {{ $server->transport()->label() }}">
                        <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'server'])</span>
                        <span class="nav-label">
                            {{ $server->name }}
                            <span class="nav-transport">{{ $server->transport()->label() }}</span>
                        </span>
                    </a>
                    <button type="button" class="nav-collapse" data-group-toggle
                            aria-controls="nav-{{ $server->slug() }}" aria-expanded="true"
                            aria-label="Collapse {{ $server->name }}">
                        <svg viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5 6 7.5 9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="nav-items" id="nav-{{ $server->slug() }}">
                <a class="nav-item" href="#{{ $server->slug() }}-install" title="Connect">
                    <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'plug'])</span>
                    <span class="nav-label">Connect</span>
                </a>
                @if ($server->tools)
                    <a class="nav-item" href="#{{ $server->slug() }}-tools" title="Tools ({{ count($server->tools) }})">
                        <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'tool'])</span>
                        <span class="nav-label">Tools</span>
                        <span class="count">{{ count($server->tools) }}</span>
                    </a>
                @endif
                @if ($server->resources || $server->resourceTemplates)
                    <a class="nav-item" href="#{{ $server->slug() }}-resources" title="Resources ({{ count($server->resources) + count($server->resourceTemplates) }})">
                        <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'doc'])</span>
                        <span class="nav-label">Resources</span>
                        <span class="count">{{ count($server->resources) + count($server->resourceTemplates) }}</span>
                    </a>
                @endif
                @if ($server->prompts)
                    <a class="nav-item" href="#{{ $server->slug() }}-prompts" title="Prompts ({{ count($server->prompts) }})">
                        <span class="nav-icon">@include('mcp-docs::partials.icon', ['name' => 'chat'])</span>
                        <span class="nav-label">Prompts</span>
                        <span class="count">{{ count($server->prompts) }}</span>
                    </a>
                @endif
                </div>
            </div>
        @endforeach
    </nav>

    @if ($switcher)
        <div class="theme-toggle" role="group" aria-label="Colour theme">
            @foreach (['light' => 'sun', 'dark' => 'moon', 'system' => 'monitor'] as $choice => $icon)
                <button type="button" data-theme-choice="{{ $choice }}"
                        aria-pressed="{{ $choice === 'system' ? 'true' : 'false' }}"
                        title="{{ ucfirst($choice) }}">
                    @include('mcp-docs::partials.icon', ['name' => $icon])
                    <span class="nav-label">{{ ucfirst($choice) }}</span>
                </button>
            @endforeach
        </div>
    @endif
</aside>

<main id="content" class="content">

    @foreach ($document->warnings as $warning)
        <p class="alert" role="status">{{ $warning }}</p>
    @endforeach

    @if ($intro->isEnabled())
        <section id="what-is-mcp" class="intro">
            <h1>{{ $intro->heading($title) }}</h1>

            <p class="lede">{!! LaravelMcpDocs\Support\Text::inline($intro->lede()) !!}</p>

            @foreach ($intro->overview() as $paragraph)
                <p class="overview">{!! LaravelMcpDocs\Support\Text::inline($paragraph) !!}</p>
            @endforeach

            @if ($intro->cards())
                <div class="cards">
                    @foreach ($intro->cards() as $card)
                        <div class="card">
                            @if ($card['title'])
                                <h3>{!! LaravelMcpDocs\Support\Text::inline($card['title']) !!}</h3>
                            @endif
                            @if ($card['body'])
                                <p>{!! LaravelMcpDocs\Support\Text::inline($card['body']) !!}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($intro->footnote())
                <p class="generated">{!! LaravelMcpDocs\Support\Text::inline($intro->footnote()) !!}</p>
            @endif

            <p class="access access-{{ $access->isPublic() ? 'public' : 'restricted' }}">
                <span class="badge {{ $access->isPublic() ? 'badge-warn' : 'badge-safe' }}">{{ $access->label() }}</span>
                {{ $access->description() }}
            </p>
        </section>
    @endif

    @forelse ($document->servers as $server)
        @include('mcp-docs::partials.server', [
            'server' => $server,
            'clients' => $clients,
            'walkthroughs' => $walkthroughs[$server->slug()] ?? [],
        ])
    @empty
        <section class="empty">
            <h2>No MCP servers are registered</h2>
            <p>Register one in <code>routes/ai.php</code> and it will show up here:</p>
            <pre><code>Mcp::local('demo', App\Mcp\Servers\Demo::class);
Mcp::web('mcp/demo', App\Mcp\Servers\Demo::class);</code></pre>
        </section>
    @endforelse

    @if ($branding->footer())
        <footer class="footer">{!! LaravelMcpDocs\Support\Text::inline($branding->footer()) !!}</footer>
    @endif
</main>

<script>{!! $js !!}</script>
</body>
</html>
