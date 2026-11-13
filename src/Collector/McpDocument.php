<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

class McpDocument
{
    /**
     * Holds the collected documentation for every registered MCP server.
     *
     * @param  list<ServerDocument>  $servers
     * @param  list<string>  $warnings  Problems that did not stop collection.
     */
    public function __construct(
        public readonly array $servers,
        public readonly array $warnings = [],
    ) {
    }

    /**
     * Whether no server was documented.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->servers === [];
    }

    /**
     * The document as a plain array of servers and warnings.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'servers' => array_map(fn (ServerDocument $s): array => $s->toArray(), $this->servers),
            'warnings' => $this->warnings,
        ];
    }

    /**
     * The document encoded as pretty-printed JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
