<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
});

it('falls back to the application name', function (): void {
    config()->set('app.name', 'Acme');
    config()->set('mcp-docs.branding.name', null);

    $this->get('/mcp/docs')->assertSee('Acme MCP');
});

it('shows the configured name and tagline', function (): void {
    config()->set('mcp-docs.branding.name', 'Acme Developer Platform');
    config()->set('mcp-docs.branding.tagline', 'Internal tools for agents');

    $this->get('/mcp/docs')
        ->assertSee('Acme Developer Platform')
        ->assertSee('Internal tools for agents');
});

it('renders a remote logo as an image', function (): void {
    config()->set('mcp-docs.branding.logo', 'https://acme.test/logo.png');

    $this->get('/mcp/docs')->assertSee('src="https://acme.test/logo.png"', false);
});

it('inlines an svg logo from disk', function (): void {
    $path = sys_get_temp_dir().'/mcp-logo-'.getmypid().'.svg';
    file_put_contents($path, '<svg viewBox="0 0 10 10"><title>Acme</title></svg>');

    config()->set('mcp-docs.branding.logo', $path);

    $this->get('/mcp/docs')->assertSee('<svg viewBox="0 0 10 10">', false);

    unlink($path);
});

it('serves a separate logo for dark backgrounds', function (): void {
    config()->set('mcp-docs.branding.logo', '/light.png');
    config()->set('mcp-docs.branding.logo_dark', '/dark.png');

    $this->get('/mcp/docs')
        ->assertSee('data-logo-theme="light"', false)
        ->assertSee('data-logo-theme="dark"', false)
        ->assertSee('src="/dark.png"', false);
});

it('applies an accent colour override to every theme', function (): void {
    config()->set('mcp-docs.branding.accent', '#ff6600');

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain(':root{--accent:#ff6600}')
        ->toContain(':root[data-theme="dark"]{--accent:#ff6600}');
});

it('refuses to inline an accent that is not a colour', function (): void {
    config()->set('mcp-docs.branding.accent', 'red;} body{display:none');

    $this->get('/mcp/docs')->assertDontSee('body{display:none', false);
});

it('shows branding links and a footer', function (): void {
    config()->set('mcp-docs.branding.links', ['Support' => 'https://acme.test/support']);
    config()->set('mcp-docs.branding.footer', 'Questions? Ask in `#platform`.');

    $this->get('/mcp/docs')
        ->assertSee('https://acme.test/support', false)
        ->assertSee('<code>#platform</code>', false);
});

it('uses a configured favicon', function (): void {
    config()->set('mcp-docs.branding.favicon', '/favicon-mcp.png');

    $this->get('/mcp/docs')->assertSee('<link rel="icon" href="/favicon-mcp.png">', false);
});

it('keeps a single logo visible on dark backgrounds', function (): void {
    config()->set('mcp-docs.branding.logo', '/logo.png');
    config()->set('mcp-docs.branding.logo_dark', null);

    // Without a dark counterpart there is nothing to swap to, so the theme
    // rules must not apply at all.
    $this->get('/mcp/docs')->assertDontSee('<div class="brand" data-dual-logo>', false);
});

it('swaps logos only when both are configured', function (): void {
    config()->set('mcp-docs.branding.logo', '/light.png');
    config()->set('mcp-docs.branding.logo_dark', '/dark.png');

    $this->get('/mcp/docs')->assertSee('<div class="brand" data-dual-logo>', false);
});

it('embeds a raster logo on disk as a data URI', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    config()->set('mcp-docs.branding.logo', $path);

    $this->get('/mcp/docs')
        ->assertSee('src="data:image/png;base64,', false)
        ->assertDontSee($path);

    unlink($path);
});

it('opens the sidebar links in a new tab', function (): void {
    config()->set('mcp-docs.branding.links', ['Support' => 'https://example.test/support']);

    $this->get('/mcp/docs')
        ->assertSee('href="https://example.test/support" target="_blank" rel="noopener noreferrer"', false);
});
