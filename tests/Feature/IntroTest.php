<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
});

it('explains MCP by default', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('What MCP is')
        ->assertSee('What a tool is')
        ->assertSee('How to connect')
        ->assertSee('Model Context Protocol');
});

it('takes a custom heading and lede', function (): void {
    config()->set('mcp-docs.intro.heading', 'Order tools for agents');
    config()->set('mcp-docs.intro.lede', 'Everything our assistant can do with orders.');

    $this->get('/mcp/docs')
        ->assertSee('Order tools for agents')
        ->assertSee('Everything our assistant can do with orders.');
});

it('renders an overview of the servers being documented', function (): void {
    config()->set('mcp-docs.intro.overview', [
        'These servers give support agents read access to the order book.',
        'Access is scoped to your own account.',
    ]);

    $this->get('/mcp/docs')
        ->assertSee('read access to the order book')
        ->assertSee('scoped to your own account');
});

it('splits a single overview string into paragraphs', function (): void {
    config()->set('mcp-docs.intro.overview', "First paragraph.\n\nSecond paragraph.");

    $page = $this->get('/mcp/docs')->getContent();

    expect(substr_count($page, '<p class="overview">'))->toBe(2);
});

it('renders inline markdown in the overview', function (): void {
    config()->set('mcp-docs.intro.overview', 'Call `search-orders` to **find** an order.');

    $this->get('/mcp/docs')
        ->assertSee('<code>search-orders</code>', false)
        ->assertSee('<strong>find</strong>', false);
});

it('replaces the explainer cards', function (): void {
    config()->set('mcp-docs.intro.cards', [
        ['title' => 'Who this is for', 'body' => 'Support agents.'],
        ['title' => 'What it can do', 'body' => 'Look up and create orders.'],
    ]);

    $this->get('/mcp/docs')
        ->assertSee('Who this is for')
        ->assertSee('Support agents.')
        ->assertDontSee('What MCP is');
});

it('removes the cards when given an empty array', function (): void {
    config()->set('mcp-docs.intro.cards', []);

    $this->get('/mcp/docs')
        ->assertDontSee('What MCP is')
        ->assertDontSee('<div class="cards">', false);
});

it('drops the footnote when set to null', function (): void {
    config()->set('mcp-docs.intro.footnote', null);

    $this->get('/mcp/docs')->assertDontSee('read from the running servers');
});

it('takes a custom footnote', function (): void {
    config()->set('mcp-docs.intro.footnote', 'Regenerated on every deploy.');

    $this->get('/mcp/docs')->assertSee('Regenerated on every deploy.');
});

it('can drop the whole section', function (): void {
    config()->set('mcp-docs.intro.enabled', false);

    $this->get('/mcp/docs')
        ->assertSuccessful()
        ->assertDontSee('What MCP is')
        // The servers themselves must still render.
        ->assertSee('search-orders');
});

it('ignores malformed cards instead of breaking the page', function (): void {
    config()->set('mcp-docs.intro.cards', [
        'not an array',
        ['title' => 'Kept', 'body' => 'Valid.'],
        ['nonsense' => true],
    ]);

    $this->get('/mcp/docs')
        ->assertSuccessful()
        ->assertSee('Kept');
});
