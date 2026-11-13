<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

it('summarises every server', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $this->artisan('mcp:docs:collect')
        ->expectsOutputToContain('Orders')
        ->expectsOutputToContain('search-orders')
        ->assertSuccessful();
});

it('writes a json manifest to disk', function (): void {
    Mcp::local('orders', OrdersServer::class);

    $path = sys_get_temp_dir().'/mcp-docs-'.getmypid().'.json';

    $this->artisan('mcp:docs:collect', ['--output' => $path])->assertSuccessful();

    expect($path)->toBeFile();

    $manifest = json_decode((string) file_get_contents($path), true);

    expect($manifest['servers'][0]['name'])->toBe('Orders')
        ->and($manifest['servers'][0]['tools'])->toHaveCount(2);

    unlink($path);
});

it('warns when nothing is registered', function (): void {
    $this->artisan('mcp:docs:collect')
        ->expectsOutputToContain('No MCP servers were discovered')
        ->assertSuccessful();
});
