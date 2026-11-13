<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

beforeEach(function (): void {
    Mcp::local('orders', OrdersServer::class);
});

it('follows the system preference by default', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    // No override on <html> means prefers-color-scheme decides.
    expect($page)->not->toContain('<html lang="en" data-theme=');
    expect($page)->toContain('data-theme-choice="system"');
});

it('locks to a configured default theme', function (): void {
    config()->set('mcp-docs.theme.default', 'dark');

    $this->get('/mcp/docs')->assertSee('data-theme="dark"', false);
});

it('ignores an unknown default theme', function (): void {
    config()->set('mcp-docs.theme.default', 'sepia');

    expect($this->get('/mcp/docs')->getContent())->not->toContain('data-theme="sepia"');
});

it('can hide the switcher entirely', function (): void {
    config()->set('mcp-docs.theme.switcher', false);

    $this->get('/mcp/docs')
        ->assertDontSee('class="theme-toggle"', false)
        ->assertDontSee('<button type="button" data-theme-choice', false);
});

it('does not reapply a stored theme when the switcher is off', function (): void {
    config()->set('mcp-docs.theme.switcher', false);

    $page = $this->get('/mcp/docs')->getContent();

    // Neither the pre-paint script nor the toggle wiring may run.
    expect($page)->not->toContain("localStorage.getItem('mcp-docs-theme')");
    expect($page)->toContain('if (!buttons.length)');
});

it('offers all three theme choices', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('data-theme-choice="light"', false)
        ->assertSee('data-theme-choice="dark"', false)
        ->assertSee('data-theme-choice="system"', false);
});

it('defines every colour for light, system-dark and forced-dark', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    expect($css)
        ->toContain('@media (prefers-color-scheme: dark) {')
        ->toContain(':root:not([data-theme="light"])')
        ->toContain(':root[data-theme="dark"]');

    // Every token must have a light definition on bare :root, or a viewer whose
    // system is light and who picks "light" gets an unstyled page.
    $root = substr($css, strpos($css, ':root {'), strpos($css, '/* System preference') - strpos($css, ':root {'));

    foreach (['--bg', '--surface', '--border', '--text', '--muted', '--accent'] as $token) {
        expect($root)->toContain($token.':');
    }
});

it('applies a stored choice before the page paints', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    $script = strpos($page, "localStorage.getItem('mcp-docs-theme')");
    $body = strpos($page, '<body>');

    expect($script)->not->toBeFalse()
        ->and($script)->toBeLessThan($body);
});
