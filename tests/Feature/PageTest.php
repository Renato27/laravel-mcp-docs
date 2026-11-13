<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

it('renders the documentation page', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSuccessful()
        ->assertSee('Orders')
        ->assertSee('search-orders')
        ->assertSee('Create an order from a cart.')
        ->assertSee('Claude Code');
});

it('shows the human prose and the model prose separately', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('Creates an order from an existing cart.')
        ->assertSee('What the assistant is told');
});

it('renders the input schema as a table', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('Customer name or e-mail.')
        ->assertSee('idempotency_key')
        ->assertSee('defaults to');
});

it('offers an install snippet per client', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('claude mcp add orders -- php artisan mcp:start orders')
        ->assertSee('.cursor/mcp.json')
        ->assertSee('context_servers');
});

it('exposes the manifest as json', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->getJson('/mcp/docs.json')
        ->assertSuccessful()
        ->assertJsonPath('servers.0.name', 'Orders')
        ->assertJsonCount(2, 'servers.0.tools');
});

it('explains itself when nothing is registered', function (): void {
    $this->get('/mcp/docs')
        ->assertSuccessful()
        ->assertSee('No MCP servers are registered');
});

it('keeps two servers that share a name apart', function (): void {
    // Both registrations point at the same server class, so they report the
    // same name; only the registration key distinguishes them.
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);

    $document = app(LaravelMcpDocs\Collector\ServerCollector::class)->collect();

    $slugs = array_map(fn ($server) => $server->slug(), $document->servers);

    expect($slugs)->toEqual(['orders', 'mcp-orders']);
});

it('gives each server the snippet for its own transport', function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('claude mcp add orders -- php artisan mcp:start orders')
        ->toContain('claude mcp add --transport http mcp-orders');
});

it('renders backticks in docblocks as code', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('<code>reference</code>', false)
        ->assertDontSee('`reference`');
});
