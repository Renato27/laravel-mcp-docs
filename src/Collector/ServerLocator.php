<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Registrar;
use ReflectionFunction;
use LaravelMcpDocs\Enums\ServerTransport;

class ServerLocator
{
    /**
     * Creates the locator over the MCP registrar, URL generator, config and router.
     *
     * @param  Registrar  $registrar
     * @param  UrlGenerator  $url
     * @param  Config  $config
     * @param  Router  $router
     */
    public function __construct(
        protected Registrar $registrar,
        protected UrlGenerator $url,
        protected Config $config,
        protected Router $router,
    ) {
    }

    /**
     * Discovers every MCP server this application registers, plus those named only in config, filtered by config.
     *
     * A server whose class cannot be recovered is skipped rather than guessed at.
     *
     * @return list<RegisteredServer>
     */
    public function all(): array
    {
        $servers = [];

        $registrations = $this->registrar->servers();

        foreach ($registrations as $key => $registration) {
            $serverClass = $this->resolveServerClass($key, $registration);

            if ($serverClass !== null) {
                $servers[] = $this->registered((string) $key, $serverClass, $registration instanceof Route);
            }
        }

        return $this->applyFilters([...$servers, ...$this->unregisteredOverrides($registrations)]);
    }

    /**
     * A server as documented: over HTTP at its public URL, or as a local handle.
     *
     * @param  string  $key
     * @param  class-string<Server>  $serverClass
     * @param  bool  $isHttp
     * @return RegisteredServer
     */
    protected function registered(string $key, string $serverClass, bool $isHttp): RegisteredServer
    {
        return new RegisteredServer(
            key: $key,
            serverClass: $serverClass,
            transport: $isHttp ? ServerTransport::Http : ServerTransport::Stdio,
            url: $isHttp ? $this->publicUrl($key) : null,
        );
    }

    /**
     * The URL clients connect to: the one configured under `mcp-docs.urls`, the route's own URL otherwise.
     *
     * @param  string  $key
     * @return string
     */
    protected function publicUrl(string $key): string
    {
        $urls = $this->config->get('mcp-docs.urls', []);
        $configured = is_array($urls) ? ($urls[$key] ?? null) : null;

        return is_string($configured) && $configured !== '' ? $configured : $this->url->to($key);
    }

    /**
     * Servers named in config that the registrar does not hold, as under `route:cache`.
     *
     * A configured key that matches a registered route is documented as HTTP; any other key as a local handle.
     *
     * @param  array<string|int, mixed>  $registrations
     * @return list<RegisteredServer>
     */
    protected function unregisteredOverrides(array $registrations): array
    {
        $overrides = $this->config->get('mcp-docs.servers', []);

        if (! is_array($overrides)) {
            return [];
        }

        $servers = [];

        foreach ($overrides as $key => $class) {
            if (array_key_exists($key, $registrations) || ! is_string($class)) {
                continue;
            }

            $serverClass = $this->validateServerClass($class);

            if ($serverClass !== null) {
                $servers[] = $this->registered((string) $key, $serverClass, $this->routeExists((string) $key));
            }
        }

        return $servers;
    }

    /**
     * Whether a route with this URI is registered.
     *
     * @param  string  $uri
     * @return bool
     */
    protected function routeExists(string $uri): bool
    {
        $uri = trim($uri, '/');

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return true;
            }
        }

        return false;
    }

    /**
     * The server class behind a registration: the configured override, else the class its closure captured.
     *
     * Both `Mcp::web()` and `Mcp::local()` register arrow functions that capture the server class by value.
     *
     * @param  string|int  $key
     * @param  mixed  $registration
     * @return class-string<Server>|null
     */
    protected function resolveServerClass(string|int $key, mixed $registration): ?string
    {
        $overrides = $this->config->get('mcp-docs.servers', []);

        if (is_array($overrides) && is_string($overrides[$key] ?? null)) {
            return $this->validateServerClass($overrides[$key]);
        }

        $closure = $registration instanceof Route
            ? $registration->getAction('uses')
            : $registration;

        if (! $closure instanceof Closure) {
            return null;
        }

        $captured = (new ReflectionFunction($closure))->getClosureUsedVariables();

        $candidate = $captured['serverClass'] ?? null;

        return is_string($candidate) ? $this->validateServerClass($candidate) : null;
    }

    /**
     * The candidate when it is an MCP server class, null otherwise.
     *
     * @param  string  $candidate
     * @return class-string<Server>|null
     */
    protected function validateServerClass(string $candidate): ?string
    {
        return is_subclass_of($candidate, Server::class) ? $candidate : null;
    }

    /**
     * Keeps the servers allowed by `mcp-docs.only` and not excluded by `mcp-docs.except`, by class or key.
     *
     * @param  list<RegisteredServer>  $servers
     * @return list<RegisteredServer>
     */
    protected function applyFilters(array $servers): array
    {
        $only = (array) $this->config->get('mcp-docs.only', []);
        $except = (array) $this->config->get('mcp-docs.except', []);

        $named = fn (RegisteredServer $server, array $names): bool => in_array($server->serverClass, $names, true)
            || in_array($server->key, $names, true);

        return array_values(array_filter(
            $servers,
            fn (RegisteredServer $server): bool => ($only === [] || $named($server, $only)) && ! $named($server, $except),
        ));
    }
}
