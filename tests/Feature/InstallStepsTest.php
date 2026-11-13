<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\ClientRegistry;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Walkthrough;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

/**
 * @return array{0: list<Client>, 1: LaravelMcpDocs\Collector\ServerDocument}
 */
function clientsAndServer(bool $http = false): array
{
    $http
        ? Mcp::web('mcp/orders', OrdersServer::class)
        : Mcp::local('orders', OrdersServer::class);

    return [
        app(ClientRegistry::class)->enabled(),
        app(ServerCollector::class)->collect()->servers[0],
    ];
}

/**
 * Everything a walkthrough tells the reader, as one searchable string.
 */
function prose(Walkthrough $walkthrough): string
{
    $steps = implode(' ', array_map(
        fn (InstallStep $step): string => $step->title.' '.$step->body,
        $walkthrough->steps,
    ));

    $snippet = $walkthrough->snippet;

    return implode(' ', array_filter([
        $steps,
        $snippet?->path,
        $snippet?->code,
        $snippet === null ? null : implode(' ', $snippet->notes),
        $walkthrough->unavailable,
    ]));
}

function clientNamed(array $clients, string $key): Client
{
    return collect($clients)->first(fn (Client $c): bool => $c->key() === $key);
}

it('includes the OpenAI and DeepSeek clients', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $keys = array_map(fn (Client $c): string => $c->key(), app(ClientRegistry::class)->enabled());

    expect($keys)->toContain('chatgpt')->toContain('codex')->toContain('deepseek');

    $this->get('/mcp/docs')
        ->assertSee('OpenAI Codex')
        ->assertSee('ChatGPT')
        ->assertSee('DeepSeek Harness');
});

it('uses the right config format for each client', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    $expected = ['codex' => 'toml', 'deepseek' => 'yaml', 'cursor' => 'json', 'vscode' => 'json'];

    foreach ($clients as $client) {
        if (isset($expected[$client->key()])) {
            expect($client->snippet($server)->language)->toBe($expected[$client->key()]);
        }
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('writes valid TOML for Codex', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    $code = clientNamed($clients, 'codex')->snippet($server)->code;

    expect($code)->toStartWith('[mcp_servers.')
        ->and($code)->toContain($http ? 'url = "' : 'command = "php"');
})->with(['stdio' => [false], 'http' => [true]]);

it('writes a single dsh plugin entry for DeepSeek', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    $code = clientNamed($clients, 'deepseek')->snippet($server)->code;

    expect($code)->toContain("name: '@deepseek-ai/dsh-mcp-client'")
        ->and($code)->toContain('serverName:')
        ->and($code)->toContain($http ? 'transport: streamable-http' : 'transport: stdio');
})->with(['stdio' => [false], 'http' => [true]]);

it('offers both a config file and a click-through route', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('Config file')
        ->assertSee('Step by step');
});

// Per operating system -------------------------------------------------------

it('answers for every operating system, exactly once', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    foreach ($clients as $client) {
        $covered = [];

        foreach ($client->walkthroughs($server) as $walkthrough) {
            foreach ($walkthrough->platforms as $os) {
                $covered[] = $os->value;
            }
        }

        sort($covered);

        // A reader whose platform is missing is left guessing, and one whose
        // platform appears twice is shown two answers to the same question.
        expect($covered)->toEqual(['linux', 'macos', 'windows'], "[{$client->key()}] does not cover every platform once");
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('merges platforms that read identically into one tab', function (): void {
    [$clients, $server] = clientsAndServer();

    // Nothing in the Codex UI walkthrough differs between macOS and Linux, so
    // offering the reader a choice between them would be noise.
    $codex = clientNamed($clients, 'codex')->walkthroughs($server);

    expect($codex)->toHaveCount(2)
        ->and($codex[0]->label())->toBe('macOS & Linux')
        ->and($codex[1]->label())->toBe('Windows');

    // ChatGPT is reached through a browser, so nothing about it depends on
    // the operating system at all.
    Mcp::web('mcp/orders', OrdersServer::class);
    $remote = app(ServerCollector::class)->collect()->servers[1];

    expect(clientNamed($clients, 'chatgpt')->walkthroughs($remote))->toHaveCount(1);
});

it('never shows one platform another platform\'s paths or shortcuts', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    // Each platform's giveaways: things that cannot be true anywhere else.
    $foreign = [
        'macos' => ['%USERPROFILE%', '%APPDATA%'],
        'linux' => ['%USERPROFILE%', '%APPDATA%', 'Cmd '],
        'windows' => ['~/', 'Cmd ', 'menu bar'],
    ];

    foreach ($clients as $client) {
        foreach ($client->walkthroughs($server) as $walkthrough) {
            $text = prose($walkthrough);

            foreach ($walkthrough->platforms as $os) {
                foreach ($foreign[$os->value] as $giveaway) {
                    expect(str_contains($text, $giveaway))->toBeFalse(
                        "[{$client->key()}] shows {$os->label()} readers \"{$giveaway}\"",
                    );
                }
            }
        }
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('gives Claude Desktop the settings path its platform actually uses', function (): void {
    [$clients, $server] = clientsAndServer();

    $by = [];
    foreach (clientNamed($clients, 'claude-desktop')->walkthroughs($server) as $walkthrough) {
        $by[$walkthrough->platforms[0]->value] = prose($walkthrough);
    }

    expect($by['macos'])->toContain('~/Library/Application Support/Claude/claude_desktop_config.json')
        ->and($by['macos'])->toContain('Claude** menu in the macOS menu bar')
        ->and($by['windows'])->toContain('%APPDATA%\\Claude\\claude_desktop_config.json')
        ->and($by['windows'])->toContain('hover **File** and choose **Settings**')
        // Closing the window on Windows leaves it running in the tray, so the
        // config is never re-read.
        ->and($by['windows'])->toContain('notification area');
});

it('says so rather than inventing a route that does not exist', function (): void {
    [$clients, $server] = clientsAndServer();

    $linux = collect(clientNamed($clients, 'claude-desktop')->walkthroughs($server))
        ->first(fn (Walkthrough $w): bool => $w->covers(OperatingSystem::Linux));

    expect($linux->unavailable)->toContain('no Linux build')
        ->and($linux->steps)->toBeEmpty()
        // A dead end is only useful with a way out of it.
        ->and($linux->unavailable)->toContain('Claude Code');
});

it('sends Linux readers to the browser for a remote Claude connector', function (): void {
    [$clients, $server] = clientsAndServer(http: true);

    $linux = collect(clientNamed($clients, 'claude-desktop')->walkthroughs($server))
        ->first(fn (Walkthrough $w): bool => $w->covers(OperatingSystem::Linux));

    // Custom connectors belong to the account, so the missing desktop build
    // does not put a remote server out of reach.
    expect($linux->unavailable)->toBeNull()
        ->and(prose($linux))->toContain('claude.ai');
});

it('lets an operating system take its own screenshot', function (): void {
    Mcp::local('orders', OrdersServer::class);

    config()->set('mcp-docs.screenshots.claude-desktop', [
        'open-settings' => '/img/shared.png',
        'windows' => ['open-settings' => '/img/windows.png'],
    ]);

    $clients = app(ClientRegistry::class)->enabled();
    $server = app(ServerCollector::class)->collect()->servers[0];
    $desktop = clientNamed($clients, 'claude-desktop');

    expect($desktop->illustratedSteps($server, OperatingSystem::Windows)[0]->image)->toBe('/img/windows.png')
        ->and($desktop->illustratedSteps($server, OperatingSystem::MacOs)[0]->image)->toBe('/img/shared.png');
});

it('renders an operating system selector only where it earns its place', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)->toContain('data-os-tabs')
        ->toContain('data-os-values="macos linux"')
        ->toContain('Your system');
});

// ChatGPT --------------------------------------------------------------------

it('documents the developer-mode route into ChatGPT', function (): void {
    [$clients, $server] = clientsAndServer(http: true);

    $text = prose(clientNamed($clients, 'chatgpt')->walkthroughs($server)[0]);

    expect($text)->toContain('Developer mode')
        ->toContain('Security and login')
        ->toContain('Plugins')
        // ChatGPT dials the server from OpenAI's side, so localhost is out.
        ->toContain('public internet');
});

it('explains why a local server cannot reach ChatGPT on the web', function (): void {
    [$clients, $server] = clientsAndServer();

    $walkthroughs = clientNamed($clients, 'chatgpt')->walkthroughs($server);

    expect($walkthroughs)->toHaveCount(1)
        ->and($walkthroughs[0]->unavailable)->toContain('STDIO')
        ->and($walkthroughs[0]->unavailable)->toContain('OpenAI Codex');
});

it('names the app that actually carries the Codex MCP settings', function (): void {
    [$clients, $server] = clientsAndServer();

    // There is no "Codex desktop app": the UI ships in the ChatGPT desktop
    // app, which shares config.toml with the CLI and the IDE extension.
    $text = prose(clientNamed($clients, 'codex')->walkthroughs($server)[0]);

    expect($text)->toContain('ChatGPT desktop app')
        ->and($text)->not->toContain('Codex desktop app');
});

// Structure ------------------------------------------------------------------

it('gives every client a complete, well-formed walkthrough', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    foreach ($clients as $client) {
        foreach ($client->walkthroughs($server) as $walkthrough) {
            $where = "[{$client->key()} / {$walkthrough->label()}]";

            if ($walkthrough->unavailable !== null) {
                // No steps is only acceptable with a reason in their place.
                expect(trim($walkthrough->unavailable))->not->toBeEmpty("{$where} has an empty reason")
                    ->and($walkthrough->steps)->toBeEmpty("{$where} has both steps and a reason");

                continue;
            }

            $keys = array_map(fn (InstallStep $step): string => $step->key, $walkthrough->steps);

            expect($walkthrough->steps)->not->toBeEmpty("{$where} has no walkthrough")
                ->and($keys)->toEqual(array_unique($keys), "{$where} repeats a step key")
                ->and($walkthrough->snippet)->not->toBeNull("{$where} has no configuration");

            foreach ($walkthrough->steps as $step) {
                expect($step->key)->toMatch('/^[a-z][a-z0-9-]*$/')
                    ->and(trim($step->title))->not->toBeEmpty()
                    ->and(trim((string) $step->body))->not->toBeEmpty("{$where}.{$step->key} has no body");
            }
        }
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('cites the official documentation for every client', function (): void {
    [$clients, $server] = clientsAndServer();

    foreach ($clients as $client) {
        expect($client->docsUrl($server))->toStartWith('https://', "[{$client->key()}] cites no source");
    }
});

it('cites pages that have not moved out from under it', function (): void {
    [$clients, $server] = clientsAndServer();

    // These two both redirect now. Pinning them keeps a stale URL from
    // quietly outliving the walkthrough it is supposed to back.
    $moved = [
        'codex' => 'developers.openai.com/codex',
        'vscode' => 'docs/copilot/chat/mcp-servers',
    ];

    foreach ($moved as $key => $stale) {
        expect(clientNamed($clients, $key)->docsUrl($server))->not->toContain($stale);
    }
});

it('ends every walkthrough with a step that verifies the connection', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    foreach ($clients as $client) {
        foreach ($client->walkthroughs($server) as $walkthrough) {
            if ($walkthrough->steps === []) {
                continue;
            }

            // A walkthrough that stops at "paste this" leaves the reader unable
            // to tell whether it worked.
            $steps = $walkthrough->steps;

            expect(end($steps)->key)->toBeIn(
                ['verify', 'approve'],
                "[{$client->key()} / {$walkthrough->label()}] never tells the reader how to check it worked",
            );
        }
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('names the same config file in the steps as in the snippet', function (bool $http): void {
    [$clients, $server] = clientsAndServer($http);

    foreach ($clients as $client) {
        foreach ($client->walkthroughs($server) as $walkthrough) {
            $path = $walkthrough->snippet?->path;

            // Covers every config format the clients use: json, toml and yaml.
            if ($path === null || pathinfo($path, PATHINFO_EXTENSION) === '') {
                continue;
            }

            // Windows paths are not split by pathinfo() on a POSIX host, which
            // is fine: the whole path is what the steps should name anyway.
            $file = basename(str_replace('\\', '/', $path));

            $prose = implode(' ', array_map(
                fn (InstallStep $step): string => $step->title.' '.$step->body,
                $walkthrough->steps,
            ));

            expect(str_contains($prose, $file))->toBeTrue(
                "[{$client->key()} / {$walkthrough->label()}] steps never mention {$file}",
            );
        }
    }
})->with(['stdio' => [false], 'http' => [true]]);

it('adapts the walkthrough to the transport', function (): void {
    Mcp::local('orders', OrdersServer::class);
    Mcp::web('mcp/orders', OrdersServer::class);

    $page = $this->get('/mcp/docs')->getContent();

    expect($page)
        ->toContain('Choose the command (stdio) server type')
        ->toContain('Choose the HTTP server type')
        ->toContain('Add Local Server')
        ->toContain('Add Remote Server');
});

it('routes Claude Desktop HTTP servers through custom connectors, not a bridge', function (): void {
    [$clients, $server] = clientsAndServer(http: true);

    $desktop = clientNamed($clients, 'claude-desktop');

    expect(prose($desktop->walkthroughs($server)[0]))->toContain('Add custom connector')
        ->and($desktop->snippet($server)->code)->not->toContain('mcp-remote');
});

it('uses a plain url for Zed remote servers', function (): void {
    [$clients, $server] = clientsAndServer(http: true);

    $zed = clientNamed($clients, 'zed');
    $config = json_decode($zed->snippet($server)->code, true);

    expect($config['context_servers']['mcp-orders'])->toHaveKey('url')
        ->and($zed->snippet($server)->code)->not->toContain('mcp-remote');
});

it('attaches a configured screenshot to a step', function (): void {
    Mcp::local('orders', OrdersServer::class);

    config()->set('mcp-docs.screenshots.cursor.paste', '/img/cursor.png');

    $this->get('/mcp/docs')->assertSee('src="/img/cursor.png"', false);
});

it('accepts a screenshot with a caption', function (): void {
    Mcp::local('orders', OrdersServer::class);

    config()->set('mcp-docs.screenshots.cursor.customize', [
        'src' => '/img/cursor-mcp.png',
        'caption' => 'The Customize page in Cursor',
    ]);

    $this->get('/mcp/docs')
        ->assertSee('src="/img/cursor-mcp.png"', false)
        ->assertSee('The Customize page in Cursor');
});

it('renders steps without images when none are configured', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSuccessful()
        ->assertDontSee('<figure class="step-figure">', false);
});

it('shows when the walkthrough was last checked', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->get('/mcp/docs')
        ->assertSee('Checked against')
        ->assertSee('https://cursor.com/docs/mcp', false);
});

it('lists the screenshot keys from the console', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->artisan('mcp:docs:steps')
        ->expectsOutputToContain('mcp-docs.screenshots.cursor.macos.create-file')
        ->assertSuccessful();
});

it('can list the keys for one client only', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->artisan('mcp:docs:steps', ['--client' => 'deepseek'])
        // The macOS & Linux tab shares one key, because scoping it to a single
        // platform is what would split the tab in two.
        ->expectsOutputToContain('mcp-docs.screenshots.deepseek.add-plugin')
        ->expectsOutputToContain('mcp-docs.screenshots.deepseek.windows.add-plugin')
        ->doesntExpectOutputToContain('mcp-docs.screenshots.cursor.')
        ->assertSuccessful();
});

it('can list the keys for one platform only', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->artisan('mcp:docs:steps', ['--os' => 'windows'])
        ->expectsOutputToContain('mcp-docs.screenshots.cursor.windows.verify')
        ->doesntExpectOutputToContain('mcp-docs.screenshots.cursor.macos.')
        ->assertSuccessful();
});
