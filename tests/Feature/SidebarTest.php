<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);
});

it('offers a control to collapse the whole sidebar', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('data-sidebar-toggle', false)
        ->assertSee('aria-controls="sidebar"', false);
});

it('collapses to an icon rail rather than hiding the sidebar', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    // The rail keeps every destination one click away.
    expect($css)->toContain(':root[data-sidebar="collapsed"] { --sidebar: 62px; }')
        ->and($css)->not->toContain('translateX(-100%)');
});

it('gives every nav entry an icon so the rail stays usable', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    // One icon per lead, per server, and per item of the two servers.
    expect(substr_count($page, '<span class="nav-icon">'))->toBe(11)
        ->and($page)->toContain('title="Tools (2)"');
});

it('offers a control to collapse each server group', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    // Count the buttons, not the selector string inside the inlined script.
    expect(substr_count($page, 'class="nav-collapse" data-group-toggle'))->toBe(2)
        ->and($page)->toContain('aria-controls="nav-orders"')
        ->and($page)->toContain('aria-controls="nav-mcp-orders"')
        ->and($page)->toContain('id="nav-orders"')
        ->and($page)->toContain('id="nav-mcp-orders"');
});

it('starts expanded', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    preg_match('/<html[^>]*>/', $page, $html);

    expect($html[0])->not->toContain('data-sidebar')
        ->and($page)->toContain('<div class="nav-items" id="nav-orders">')
        ->and($page)->not->toContain('id="nav-orders" hidden');
});

it('keeps the sidebar reachable on narrow screens', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    // A 62px rail across the full width would be a useless strip of icons.
    expect($css)->toContain(':root[data-sidebar="collapsed"] { --sidebar: auto; }')
        ->and($css)->toContain(':root[data-sidebar="collapsed"] .nav-label,');
});

it('respects reduced motion', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    expect($css)->toContain('@media (prefers-reduced-motion: reduce)');
});

it('falls back to an initial when there is no logo for the rail', function (): void {
    config()->set('mcp-docs.branding.name', 'Acme Platform');
    config()->set('mcp-docs.branding.logo', null);

    $this->get('/mcp/docs')->assertSee('<span class="brand-initial" aria-hidden="true">A</span>', false);
});

it('shows the logo instead of an initial when one is configured', function (): void {
    config()->set('mcp-docs.branding.logo', '/logo.png');

    $this->get('/mcp/docs')->assertDontSee('class="brand-initial"', false);
});
