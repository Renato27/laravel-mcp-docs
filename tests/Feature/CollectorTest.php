<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Enums\ServerTransport;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;
use LaravelMcpDocs\Tests\Fixtures\Tools\SearchOrders;

function collectDocs(): LaravelMcpDocs\Collector\McpDocument
{
    return app(ServerCollector::class)->collect();
}

it('discovers a web server and recovers its class', function (): void {
    Mcp::web('mcp/orders', OrdersServer::class);

    $document = collectDocs();

    expect($document->servers)->toHaveCount(1);

    $server = $document->servers[0];

    expect($server->registration->serverClass)->toBe(OrdersServer::class)
        ->and($server->registration->transport)->toBe(ServerTransport::Http)
        ->and($server->registration->key)->toBe('mcp/orders')
        ->and($server->endpoint())->toEndWith('/mcp/orders');
});

it('discovers a local server and describes how to start it', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $server = collectDocs()->servers[0];

    expect($server->registration->transport)->toBe(ServerTransport::Stdio)
        ->and($server->endpoint())->toBe('php artisan mcp:start orders');
});

it('reads server identity from the server itself', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $server = collectDocs()->servers[0];

    expect($server->name)->toBe('Orders')
        ->and($server->version)->toBe('2.1.0')
        ->and($server->instructions)->toContain('look up and create customer orders')
        ->and($server->supportedProtocolVersions)->not->toBeEmpty()
        ->and($server->protocolVersion)->toBe($server->supportedProtocolVersions[0]);
});

it('collects tools straight off the wire', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $server = collectDocs()->servers[0];

    expect($server->tools)->toHaveCount(2);

    $search = collect($server->tools)->firstWhere('name', 'search-orders');

    expect($search)->not->toBeNull()
        ->and($search->class)->toBe(SearchOrders::class)
        ->and($search->description())->toBe('Search orders by customer, status or reference.')
        ->and($search->inputSchema())->toHaveKey('properties')
        ->and(array_keys($search->inputSchema()['properties']))->toEqual(['customer', 'status', 'limit'])
        ->and($search->annotations())->toMatchArray(['readOnlyHint' => true, 'idempotentHint' => true]);
});

it('separates the model-facing description from the human prose', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $server = collectDocs()->servers[0];
    $create = collect($server->tools)->firstWhere('name', 'create-order');

    expect($create->description())->toBe('Create an order from a cart.')
        ->and($create->docBlock->summary)->toBe('Creates an order from an existing cart.')
        ->and($create->docBlock->description)->toContain('idempotency_key');
});

it('collects resources and prompts', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $server = collectDocs()->servers[0];

    expect($server->resources)->toHaveCount(1)
        ->and($server->resources[0]->name)->toBe('order-policy')
        ->and($server->prompts)->toHaveCount(1)
        ->and($server->prompts[0]->name)->toBe('draft-refund-email');
});

it('documents every registered server', function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);

    expect(collectDocs()->servers)->toHaveCount(2);
});

it('serialises to a stable json manifest', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $array = collectDocs()->toArray();

    expect($array)->toHaveKeys(['servers', 'warnings'])
        ->and($array['servers'][0])->toHaveKeys([
            'key', 'class', 'transport', 'endpoint', 'name', 'version',
            'instructions', 'protocolVersion', 'capabilities', 'tools',
            'resources', 'resourceTemplates', 'prompts',
        ]);
});
