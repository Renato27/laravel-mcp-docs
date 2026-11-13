<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use LaravelMcpDocs\McpDocsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Sets a random application key for the test environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /**
     * Registers the Laravel MCP and package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            McpDocsServiceProvider::class,
        ];
    }
}
