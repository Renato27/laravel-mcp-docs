<?php

declare(strict_types=1);

namespace LaravelMcpDocs;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\ServiceProvider;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Collector\ServerLocator;
use LaravelMcpDocs\Console\CollectCommand;
use LaravelMcpDocs\Console\StepsCommand;
use LaravelMcpDocs\Http\Controllers\DocsController;
use LaravelMcpDocs\Http\Middleware\RestrictDocsAccess;
use LaravelMcpDocs\Install\ClientRegistry;
use LaravelMcpDocs\Support\Branding;
use LaravelMcpDocs\Support\DocsAccess;
use LaravelMcpDocs\Support\Intro;

class McpDocsServiceProvider extends ServiceProvider
{
    /**
     * The package's services, each built once by the container.
     *
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        ServerLocator::class => ServerLocator::class,
        ServerCollector::class => ServerCollector::class,
        ClientRegistry::class => ClientRegistry::class,
        Branding::class => Branding::class,
        DocsAccess::class => DocsAccess::class,
        Intro::class => Intro::class,
    ];

    /**
     * Merges the package config.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mcp-docs.php', 'mcp-docs');
    }

    /**
     * Loads the views, registers the routes and, in the console, the publish tags and commands.
     *
     * Config and views publish under their own tags and together under the bare `mcp-docs` tag.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mcp-docs');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $config = [__DIR__.'/../config/mcp-docs.php' => $this->app->configPath('mcp-docs.php')];
            $views = [__DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/mcp-docs')];

            $this->publishes($config, 'mcp-docs-config');
            $this->publishes($views, 'mcp-docs-views');
            $this->publishes($config + $views, 'mcp-docs');

            $this->commands([
                CollectCommand::class,
                StepsCommand::class,
            ]);
        }
    }

    /**
     * Registers the docs page and its JSON manifest route, always behind the access middleware, unless disabled.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        $config = $this->app->make(Config::class);

        if ($config->get('mcp-docs.route.enabled', true) !== true) {
            return;
        }

        $router = $this->app->make('router');

        $uri = rtrim((string) $config->get('mcp-docs.route.uri', 'mcp/docs'), '/');
        $name = (string) $config->get('mcp-docs.route.name', 'mcp-docs');

        $middleware = (array) $config->get('mcp-docs.route.middleware', ['web']);
        $middleware[] = RestrictDocsAccess::class;

        $router->get($uri, DocsController::class)
            ->middleware($middleware)
            ->name($name);

        $router->get($uri.'.json', [DocsController::class, 'manifest'])
            ->middleware($middleware)
            ->name($name.'.manifest');
    }
}
