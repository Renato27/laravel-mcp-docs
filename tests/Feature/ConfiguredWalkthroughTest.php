<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\ClientRegistry;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

/**
 * @return array{0: Client, 1: ServerDocument}
 */
function configuredClient(string $key): array
{
    Mcp::web('mcp/orders', OrdersServer::class);

    $client = collect(app(ClientRegistry::class)->enabled())->first(fn (Client $client): bool => $client->key() === $key);

    return [$client, app(ServerCollector::class)->collect()->servers[0]];
}

it('replaces a client\'s steps with the configured ones, filling the placeholders', function (): void {
    config()->set('mcp-docs.walkthroughs.claude-desktop.steps', [
        'open' => ['title' => 'Open **Customize**', 'body' => 'Paste {url} as {name}.'],
        'Save it',
    ]);

    [$client, $server] = configuredClient('claude-desktop');
    $steps = $client->illustratedSteps($server, OperatingSystem::MacOs);

    expect(array_map(fn (InstallStep $step): string => $step->key, $steps))->toBe(['open', 'step-2'])
        ->and($steps[0]->body)->toBe('Paste '.$server->registration->url.' as '.$server->slug().'.')
        ->and($steps[1]->title)->toBe('Save it');
});

it('lets a platform\'s own entry win over the shared one', function (): void {
    config()->set('mcp-docs.walkthroughs.cursor', [
        'steps' => ['shared' => ['title' => 'Shared step']],
        'windows' => ['steps' => ['win' => ['title' => 'Windows step']]],
    ]);

    [$client, $server] = configuredClient('cursor');

    expect($client->illustratedSteps($server, OperatingSystem::Windows)[0]->title)->toBe('Windows step')
        ->and($client->illustratedSteps($server, OperatingSystem::MacOs)[0]->title)->toBe('Shared step');
});

it('replaces the snippet with the configured one', function (): void {
    config()->set('mcp-docs.walkthroughs.zed.snippet', [
        'language' => 'json',
        'code' => '{"url": "{url}"}',
        'path' => 'settings.json',
        'notes' => ['Restart {name} afterwards.'],
    ]);

    [$client, $server] = configuredClient('zed');
    $snippet = $client->walkthroughs($server)[0]->snippet;

    expect($snippet->code)->toBe('{"url": "'.$server->registration->url.'"}')
        ->and($snippet->path)->toBe('settings.json')
        ->and($snippet->notes)->toBe(['Restart '.$server->slug().' afterwards.']);
});

it('keeps the client\'s own instructions without configuration', function (): void {
    [$client, $server] = configuredClient('claude-desktop');

    expect($client->configuredSteps($server, OperatingSystem::MacOs))->toBeNull()
        ->and($client->configuredSnippet($server, OperatingSystem::MacOs))->toBeNull()
        ->and($client->walkthroughs($server)[0]->configuredSteps)->toBeFalse();
});

it('attaches screenshots to configured steps by their key', function (): void {
    config()->set('mcp-docs.walkthroughs.claude-desktop.steps', ['open' => ['title' => 'Open it']]);
    config()->set('mcp-docs.screenshots.claude-desktop.open', '/img/open.png');

    [$client, $server] = configuredClient('claude-desktop');

    expect($client->illustratedSteps($server, OperatingSystem::MacOs)[0]->image)->toBe('/img/open.png');
});

it('does not claim the package checked configured steps', function (): void {
    config()->set('mcp-docs.clients', ['claude-desktop']);
    config()->set('mcp-docs.walkthroughs.claude-desktop.steps', ['open' => ['title' => 'Open it']]);
    Mcp::web('mcp/orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('Open it')
        ->assertDontSee('Checked against')
        ->assertSee('is the authority.');
});
