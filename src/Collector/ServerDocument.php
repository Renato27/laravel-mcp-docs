<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

use LaravelMcpDocs\Enums\ServerTransport;
use LaravelMcpDocs\Support\DocBlock;

class ServerDocument
{
    /**
     * Holds everything the documentation page knows about a single MCP server.
     *
     * @param  RegisteredServer  $registration
     * @param  string  $name
     * @param  string  $version
     * @param  string  $instructions
     * @param  string  $protocolVersion
     * @param  list<string>  $supportedProtocolVersions
     * @param  array<string, mixed>  $capabilities
     * @param  DocBlock  $docBlock
     * @param  list<PrimitiveDocument>  $tools
     * @param  list<PrimitiveDocument>  $resources
     * @param  list<PrimitiveDocument>  $resourceTemplates
     * @param  list<PrimitiveDocument>  $prompts
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly RegisteredServer $registration,
        public readonly string $name,
        public readonly string $version,
        public readonly string $instructions,
        public readonly string $protocolVersion,
        public readonly array $supportedProtocolVersions,
        public readonly array $capabilities,
        public readonly DocBlock $docBlock,
        public readonly array $tools = [],
        public readonly array $resources = [],
        public readonly array $resourceTemplates = [],
        public readonly array $prompts = [],
        public readonly array $warnings = [],
    ) {
    }

    /**
     * The transport the server is reached over.
     *
     * @return ServerTransport
     */
    public function transport(): ServerTransport
    {
        return $this->registration->transport;
    }

    /**
     * A stable, unique slug for anchors and file names, derived from the registration key.
     *
     * @return string
     */
    public function slug(): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $this->registration->key));

        return trim($slug, '-') ?: 'server';
    }

    /**
     * The command or URL a client needs in order to connect.
     *
     * @return string
     */
    public function endpoint(): string
    {
        return $this->registration->isHttp()
            ? (string) $this->registration->url
            : 'php artisan mcp:start '.$this->registration->key;
    }

    /**
     * The server and its primitives as a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->registration->key,
            'class' => $this->registration->serverClass,
            'transport' => $this->registration->transport->value,
            'endpoint' => $this->endpoint(),
            'url' => $this->registration->url,
            'name' => $this->name,
            'version' => $this->version,
            'instructions' => $this->instructions,
            'protocolVersion' => $this->protocolVersion,
            'supportedProtocolVersions' => $this->supportedProtocolVersions,
            'capabilities' => $this->capabilities,
            'docs' => $this->docBlock->toArray(),
            'tools' => array_map(fn (PrimitiveDocument $t): array => $t->toArray(), $this->tools),
            'resources' => array_map(fn (PrimitiveDocument $r): array => $r->toArray(), $this->resources),
            'resourceTemplates' => array_map(fn (PrimitiveDocument $r): array => $r->toArray(), $this->resourceTemplates),
            'prompts' => array_map(fn (PrimitiveDocument $p): array => $p->toArray(), $this->prompts),
            'warnings' => $this->warnings,
        ];
    }
}
