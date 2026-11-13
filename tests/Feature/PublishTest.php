<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use LaravelMcpDocs\McpDocsServiceProvider;

it('registers every publish tag', function (): void {
    $groups = array_keys(ServiceProvider::$publishGroups);

    expect($groups)
        ->toContain('mcp-docs')
        ->toContain('mcp-docs-config')
        ->toContain('mcp-docs-views');
});

it('publishes the config to the application config path', function (): void {
    $paths = ServiceProvider::pathsToPublish(McpDocsServiceProvider::class, 'mcp-docs-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/mcp-docs.php')
        ->and(reset($paths))->toEndWith('config/mcp-docs.php');
});

it('publishes views under vendor/mcp-docs', function (): void {
    $paths = ServiceProvider::pathsToPublish(McpDocsServiceProvider::class, 'mcp-docs-views');

    expect(reset($paths))->toEndWith('views/vendor/mcp-docs');
});

it('publishes both under the bare tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(McpDocsServiceProvider::class, 'mcp-docs');

    expect($paths)->toHaveCount(2);
});

it('actually copies the config file', function (): void {
    $target = config_path('mcp-docs.php');

    if (file_exists($target)) {
        unlink($target);
    }

    $this->artisan('vendor:publish', ['--tag' => 'mcp-docs-config'])->assertSuccessful();

    expect($target)->toBeFile()
        ->and(file_get_contents($target))->toContain("'access' =>");

    unlink($target);
});
