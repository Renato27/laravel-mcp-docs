<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

it('shows only HTTP when only a web server is registered', function (): void {
    Mcp::web('mcp/orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('HTTP')
        ->not->toContain('Local (STDIO)')
        // The install snippets follow the transport too.
        ->not->toContain('php artisan mcp:start')
        ->toContain('claude mcp add --transport http');
});

it('shows only STDIO when only a local server is registered', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('Local (STDIO)')
        ->not->toContain('claude mcp add --transport http')
        ->toContain('php artisan mcp:start orders');
});

it('shows each transport separately when both are registered', function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('Local (STDIO)')
        ->toContain('php artisan mcp:start orders')
        ->toContain('claude mcp add --transport http');
});

it('wraps every resource count in its own element', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('<span class="nav-label">Tools</span>')
        ->toContain('<span class="count">2</span>')
        ->toContain('<span class="nav-label">Resources</span>')
        ->toContain('<span class="nav-label">Prompts</span>')
        // Two of the three counts are 1, so expect both occurrences.
        ->and(substr_count($page, '<span class="count">1</span>'))->toBe(2);
});

it('documents a configured server the registrar never saw, as under route:cache', function (): void {
    Route::post('mcp/orders', fn () => null);
    config()->set('mcp-docs.servers', ['mcp/orders' => OrdersServer::class]);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->not->toContain('No MCP servers are registered')
        ->toContain('claude mcp add --transport http')
        ->not->toContain('Local (STDIO)');
});

it('documents a configured key with no route as a local server', function (): void {
    config()->set('mcp-docs.servers', ['orders' => OrdersServer::class]);

    expect($this->get('/mcp/docs')->getContent())
        ->toContain('php artisan mcp:start orders');
});

it('documents the configured public URL in place of the route', function (): void {
    Mcp::web('mcp/orders', OrdersServer::class);
    config()->set('mcp-docs.urls', ['mcp/orders' => 'https://gateway.example/mcp']);

    expect($this->get('/mcp/docs')->getContent())
        ->toContain('https://gateway.example/mcp')
        ->not->toContain('/mcp/orders"');
});
