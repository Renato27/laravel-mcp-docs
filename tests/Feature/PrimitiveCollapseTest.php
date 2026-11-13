<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);
});

it('offers a control to collapse every tool, resource and prompt', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    // Two servers, each carrying two tools, a resource and a prompt.
    expect(substr_count($page, 'class="primitive-collapse" data-group-toggle'))->toBe(8)
        ->and(substr_count($page, 'class="primitive-body"'))->toBe(8);
});

it('scopes the panel id to the server so two servers never share one', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    expect($page)->toContain('id="orders-search-orders-body"')
        ->and($page)->toContain('id="mcp-orders-search-orders-body"')
        ->and($page)->toContain('aria-controls="orders-search-orders-body"')
        ->and($page)->toContain('aria-controls="mcp-orders-search-orders-body"');
});

it('starts every primitive expanded', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    preg_match_all('/<div class="primitive-body"[^>]*>/', $page, $bodies);
    preg_match_all('/<button[^>]*class="primitive-collapse"[^>]*>/s', $page, $buttons);

    expect($bodies[0])->toHaveCount(8)
        ->and(array_filter($bodies[0], fn (string $tag): bool => str_contains($tag, 'hidden')))->toBe([])
        ->and($buttons[0])->toHaveCount(8)
        ->and(array_filter($buttons[0], fn (string $tag): bool => ! str_contains($tag, 'aria-expanded="true"')))->toBe([]);
});

it('hides a collapsed body and turns the chevron', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    expect($css)->toContain('.primitive-body[hidden] { display: none; }')
        ->and($css)->toContain('.primitive-collapse[aria-expanded="false"] svg { transform: rotate(-90deg); }');
});

it('reuses the group toggle so the collapse survives a reload', function (): void {
    $js = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.js');

    // The persisted map is keyed by panel id, which is why those ids carry the
    // server slug rather than the primitive name alone.
    expect($js)->toContain("var GROUPS_KEY = 'mcp-docs-collapsed-groups';")
        ->and($js)->toContain('collapsed[panel.id] = next;');
});

it('slugs a prose-named resource so its panel id is reachable', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    preg_match_all('/id="[^"]*"/', $page, $ids);

    $withSpaces = array_filter($ids[0], fn (string $id): bool => str_contains($id, ' '));

    expect($withSpaces)->toBe([]);
});

it('starts every primitive closed when the config says so', function (): void {
    config()->set('mcp-docs.primitives.default', 'closed');

    $page = $this->get('/mcp/docs')->getContent();

    preg_match_all('/<div class="primitive-body"[^>]*>/', $page, $bodies);
    preg_match_all('/<button[^>]*class="primitive-collapse"[^>]*>/s', $page, $buttons);

    expect($bodies[0])->toHaveCount(8)
        ->and(array_filter($bodies[0], fn (string $tag): bool => ! str_contains($tag, 'hidden')))->toBe([])
        ->and(array_filter($buttons[0], fn (string $tag): bool => ! str_contains($tag, 'aria-expanded="false"')))->toBe([]);
});

it('leaves the sidebar groups open when the cards are configured closed', function (): void {
    config()->set('mcp-docs.primitives.default', 'closed');

    // The setting is about the cards; collapsing the navigation with them
    // would hide the very index that makes a closed page worth reading.
    expect($this->get('/mcp/docs')->getContent())
        ->toContain('<div class="nav-items" id="nav-orders">');
});

it('treats an unknown value as open rather than hiding the whole page', function (): void {
    config()->set('mcp-docs.primitives.default', 'collapsed');

    $page = $this->get('/mcp/docs')->getContent();

    preg_match_all('/<div class="primitive-body"[^>]*>/', $page, $bodies);

    expect(array_filter($bodies[0], fn (string $tag): bool => str_contains($tag, 'hidden')))->toBe([]);
});

it('lets a stored choice win over the configured default', function (): void {
    $js = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.js');

    // Reading the default off the rendered panel is what lets one setting
    // serve both directions without a second source of truth.
    expect($js)->toContain('apply(collapsed[panel.id] === undefined ? panel.hidden : collapsed[panel.id] === true);')
        ->and($js)->not->toContain('delete collapsed[panel.id];');
});
