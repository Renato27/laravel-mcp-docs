<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;
use LaravelMcpDocs\Tests\Fixtures\Tools\SearchOrders;

beforeEach(function (): void {
    Mcp::web('mcp/orders', OrdersServer::class);
});

it('uses configured text, keyed by name, over the class docblock', function (): void {
    config()->set('mcp-docs.descriptions', [
        'search-orders' => ['Find an order you already know about.', 'Results follow your own permissions.'],
    ]);

    $this->get('/mcp/docs')
        ->assertSee('Find an order you already know about.')
        ->assertSee('Results follow your own permissions.')
        ->assertDontSee('matches on name or e-mail');
});

it('prefers a class key over a name key', function (): void {
    config()->set('mcp-docs.descriptions', [
        'search-orders' => 'Keyed by name.',
        SearchOrders::class => 'Keyed by class.',
    ]);

    $this->get('/mcp/docs')
        ->assertSee('Keyed by class.')
        ->assertDontSee('Keyed by name.');
});

it('documents a server from configured text', function (): void {
    config()->set('mcp-docs.descriptions', [OrdersServer::class => 'The order book, for support agents.']);

    $this->get('/mcp/docs')->assertSee('The order book, for support agents.');
});

it('shows the text written for the model by default', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('Instructions given to the assistant')
        ->assertSee('What the assistant is told')
        ->assertSee('Customer name or e-mail.')
        ->assertSee('It chooses which to call based on the description.');
});

it('hides every text written for the model, on the page and in the manifest', function (string $value): void {
    config()->set('mcp-docs.model_text', $value === 'false' ? false : $value);

    foreach (['/mcp/docs', '/mcp/docs.json'] as $uri) {
        $this->get($uri)
            ->assertOk()
            ->assertDontSee('Use this server to look up and create customer orders.')
            ->assertDontSee('Search orders by customer, status or reference.')
            ->assertDontSee('Customer name or e-mail.')
            ->assertDontSee('Order status.');
    }

    $this->get('/mcp/docs')
        ->assertDontSee('Instructions given to the assistant')
        ->assertDontSee('What the assistant is told')
        ->assertDontSee('It chooses which to call based on the description.')
        ->assertSee('It chooses which to call from what you ask.')
        ->assertSee('search-orders')
        ->assertSee('customer')
        ->assertSee('one of', false);
})->with(['false', 'no', '0']);

it('drops a description column with nothing to show', function (): void {
    config()->set('mcp-docs.model_text', false);

    $page = $this->get('/mcp/docs')->getContent();

    expect(substr_count($page, '<th scope="col">Description</th>'))
        ->toBeLessThan(substr_count($page, '<th scope="col">Name</th>'));
});

it('shows configured argument text even when the text for the model is hidden', function (): void {
    config()->set('mcp-docs.model_text', false);
    config()->set('mcp-docs.arguments', [
        'search-orders' => ['customer' => 'The customer, by name or e-mail.', 'missing' => 'Never shown.'],
        'draft-refund-email' => ['order_id' => 'The order to refund.'],
    ]);

    foreach (['/mcp/docs', '/mcp/docs.json'] as $uri) {
        $this->get($uri)
            ->assertSee('The customer, by name or e-mail.')
            ->assertSee('The order to refund.')
            ->assertDontSee('Never shown.')
            ->assertDontSee('Customer name or e-mail.');
    }
});

it('describes a nested property and a property of a list by the table path', function (): void {
    $schema = ['properties' => [
        'filters' => ['type' => 'object', 'properties' => ['city' => ['type' => 'string']]],
        'items' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['sku' => ['type' => 'string']]]],
    ]];

    $describe = Closure::bind(
        fn (array $schema, string $path, string $text): array => $this->describeProperty($schema, $path, $text),
        app(LaravelMcpDocs\Collector\ServerCollector::class),
        LaravelMcpDocs\Collector\ServerCollector::class,
    );

    $schema = $describe($describe($schema, 'filters.city', 'Town.'), 'items[].sku', 'Product code.');

    expect($schema['properties']['filters']['properties']['city']['description'])->toBe('Town.')
        ->and($schema['properties']['items']['items']['properties']['sku']['description'])->toBe('Product code.');
});

it('shows a configured video above a client\'s instructions', function (): void {
    config()->set('mcp-docs.videos', ['claude-desktop' => ['src' => '/videos/claude.mp4', 'caption' => 'Connecting from Claude']]);

    $this->get('/mcp/docs')
        ->assertSee('src="/videos/claude.mp4"', false)
        ->assertSee('Connecting from Claude');
});

it('shows no video for a client without one', function (): void {
    $this->get('/mcp/docs')->assertDontSee('<video', false);
});
