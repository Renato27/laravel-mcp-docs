<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

use Laravel\Mcp\Server;
use LaravelMcpDocs\Enums\ServerTransport;

class RegisteredServer
{
    /**
     * Holds a server as the Laravel MCP registrar knows it, before any documentation is collected.
     *
     * @param  string  $key  The route URI for web servers, the handle for local ones.
     * @param  class-string<Server>  $serverClass
     * @param  ServerTransport  $transport
     * @param  string|null  $url
     */
    public function __construct(
        public readonly string $key,
        public readonly string $serverClass,
        public readonly ServerTransport $transport,
        public readonly ?string $url = null,
    ) {
    }

    /**
     * Whether the server is reached over HTTP.
     *
     * @return bool
     */
    public function isHttp(): bool
    {
        return $this->transport === ServerTransport::Http;
    }
}
