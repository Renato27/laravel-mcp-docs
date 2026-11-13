<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Support\DocsAccess;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
});

function fromIp(string $ip): array
{
    return ['REMOTE_ADDR' => $ip];
}

function restrict(): void
{
    config()->set('mcp-docs.access.mode', 'restricted');
}

it('is public by default', function (): void {
    expect(app(DocsAccess::class)->isPublic())->toBeTrue();

    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertSuccessful();
});

it('allows localhost when restricted', function (): void {
    restrict();

    $this->get('/mcp/docs', fromIp('127.0.0.1'))->assertSuccessful();
});

it('blocks an address that is not allowed', function (): void {
    restrict();

    $this->get('/mcp/docs', fromIp('203.0.113.7'))
        ->assertForbidden()
        ->assertSee('not public');
});

it('protects the json manifest too', function (): void {
    restrict();

    $this->getJson('/mcp/docs.json', fromIp('203.0.113.7'))->assertForbidden();
});

it('lets anyone in when it is public', function (): void {
    config()->set('mcp-docs.access.mode', 'public');

    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertSuccessful();
    $this->getJson('/mcp/docs.json', fromIp('203.0.113.7'))->assertSuccessful();
});

it('accepts CIDR ranges', function (): void {
    restrict();
    config()->set('mcp-docs.access.allowed_ips', ['10.0.0.0/8']);

    $this->get('/mcp/docs', fromIp('10.14.3.9'))->assertSuccessful();
    $this->get('/mcp/docs', fromIp('11.0.0.1'))->assertForbidden();
});

it('accepts IPv6 addresses and ranges', function (): void {
    restrict();
    config()->set('mcp-docs.access.allowed_ips', ['::1', '2001:db8::/32']);

    $this->get('/mcp/docs', fromIp('2001:db8:1234::5'))->assertSuccessful();
    $this->get('/mcp/docs', fromIp('2001:dead::1'))->assertForbidden();
});

it('fails closed when restricted with an empty allow list', function (): void {
    restrict();
    config()->set('mcp-docs.access.allowed_ips', []);

    // An empty list must mean nobody, not everybody.
    $this->get('/mcp/docs', fromIp('127.0.0.1'))->assertForbidden();
});

it('treats an unrecognised mode as restricted', function (): void {
    config()->set('mcp-docs.access.mode', 'Public');  // wrong case
    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertForbidden();

    config()->set('mcp-docs.access.mode', 'open');
    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertForbidden();

    config()->set('mcp-docs.access.mode', true);
    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertForbidden();
});

it('cannot be bypassed by overriding the route middleware', function (): void {
    config()->set('mcp-docs.route.middleware', []);

    // Re-register the routes with the emptied middleware list.
    $this->refreshApplication();
    Mcp::local('orders', OrdersServer::class);
    restrict();

    $this->get('/mcp/docs', fromIp('203.0.113.7'))->assertForbidden();
});

it('shows the policy on the page', function (): void {
    restrict();

    $this->get('/mcp/docs', fromIp('127.0.0.1'))
        ->assertSee('Restricted to 2 addresses')
        ->assertSee('limited to allowed networks');

    config()->set('mcp-docs.access.mode', 'public');

    $this->get('/mcp/docs', fromIp('127.0.0.1'))
        ->assertSee('Public')
        ->assertSee('Anyone who can reach this URL');
});
