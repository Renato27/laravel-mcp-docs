<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Collector;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use Laravel\Mcp\Server\Primitive;
use Laravel\Mcp\Server\ServerContext;
use Throwable;
use LaravelMcpDocs\Support\DocBlock;
use LaravelMcpDocs\Support\ProtocolClient;

class ServerCollector
{
    /** The user the manifest is collected as, or null for a guest. */
    protected ?Authenticatable $user = null;

    /**
     * Creates the collector over the container and the server locator.
     *
     * @param  Container  $container
     * @param  ServerLocator  $locator
     */
    public function __construct(
        protected Container $container,
        protected ServerLocator $locator,
    ) {
    }

    /**
     * Collect the manifest as a given user would be served it.
     *
     * @param  Authenticatable|null  $user
     * @return static
     */
    public function actingAs(?Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Builds the documentation by talking MCP to each registered server, so it cannot drift from what they serve.
     *
     * @return McpDocument
     */
    public function collect(): McpDocument
    {
        $documents = [];
        $warnings = [];

        foreach ($this->locator->all() as $registration) {
            try {
                $documents[] = $this->collectServer($registration);
            } catch (Throwable $e) {
                $warnings[] = "Could not document [{$registration->serverClass}]: {$e->getMessage()}";
            }
        }

        return new McpDocument($documents, $warnings);
    }

    /**
     * Documents one server from its initialize context and its list methods.
     *
     * @param  RegisteredServer  $registration
     * @return ServerDocument
     */
    protected function collectServer(RegisteredServer $registration): ServerDocument
    {
        return $this->withUser(function () use ($registration): ServerDocument {
            $client = new ProtocolClient($this->container, $registration->serverClass);
            $context = $client->context();

            $warnings = [];

            $tools = $this->primitives($client, $context, 'tools/list', 'tools', $warnings);
            $resources = $this->primitives($client, $context, 'resources/list', 'resources', $warnings);
            $templates = $this->primitives($client, $context, 'resources/templates/list', 'resourceTemplates', $warnings);
            $prompts = $this->primitives($client, $context, 'prompts/list', 'prompts', $warnings);

            return new ServerDocument(
                registration: $registration,
                name: $context->implementation->name,
                version: $context->implementation->version,
                instructions: $this->showsModelText() ? $context->instructions : '',
                protocolVersion: $client->protocolVersion(),
                supportedProtocolVersions: array_values(array_map(
                    fn (mixed $v): string => is_object($v) && property_exists($v, 'value') ? (string) $v->value : (string) $v,
                    $context->supportedProtocolVersions,
                )),
                capabilities: $context->serverCapabilities,
                docBlock: $this->docBlock($registration->serverClass, $registration->key),
                tools: $tools,
                resources: $resources,
                resourceTemplates: $templates,
                prompts: $prompts,
                warnings: $warnings,
            );
        });
    }

    /**
     * List one kind of primitive and pair each entry with its PHP class.
     *
     * Primitives registered but never listed on the wire (tools behind ToolSearch) are appended, marked hidden.
     *
     * @param  ProtocolClient  $client
     * @param  ServerContext  $context
     * @param  string  $method
     * @param  string  $key  The result key of the list method, and the server context method of the same name.
     * @param  list<string>  $warnings
     * @return list<PrimitiveDocument>
     */
    protected function primitives(
        ProtocolClient $client,
        ServerContext $context,
        string $method,
        string $key,
        array &$warnings,
    ): array {
        try {
            $payloads = $client->list($method, $key);
        } catch (Throwable $e) {
            $warnings[] = "Could not list [{$method}]: {$e->getMessage()}";

            return [];
        }

        $registered = $this->resolvePrimitives($context, $key, $warnings);

        $classes = [];

        foreach ($registered as $primitive) {
            $classes[$primitive->name()] = $primitive::class;
        }

        $documents = [];
        $listed = [];

        foreach ($payloads as $payload) {
            $name = is_string($payload['name'] ?? null) ? $payload['name'] : null;

            if ($name === null) {
                continue;
            }

            $listed[$name] = true;

            $class = $classes[$name] ?? null;

            $documents[] = new PrimitiveDocument(
                name: $name,
                payload: $this->withArgumentText($name, $this->publicPayload($payload)),
                class: $class,
                docBlock: $this->docBlock($class, $name),
            );
        }

        return [...$documents, ...$this->hidden($registered, $listed)];
    }

    /**
     * Documents the registered primitives the list method did not return, marked hidden.
     *
     * @param  list<Primitive>  $registered
     * @param  array<string, true>  $listed
     * @return list<PrimitiveDocument>
     */
    protected function hidden(array $registered, array $listed): array
    {
        $documents = [];

        foreach ($registered as $primitive) {
            if (isset($listed[$primitive->name()])) {
                continue;
            }

            $documents[] = new PrimitiveDocument(
                name: $primitive->name(),
                payload: $this->withArgumentText($primitive->name(), $this->publicPayload($primitive->toArray())),
                class: $primitive::class,
                docBlock: $this->docBlock($primitive::class, $primitive->name()),
                hidden: true,
            );
        }

        return $documents;
    }

    /**
     * The human-facing prose for a server or primitive.
     *
     * Text configured under `mcp-docs.descriptions`, keyed by class or by name, wins over the class docblock.
     *
     * @param  string|null  $class
     * @param  string  $name
     * @return DocBlock
     */
    protected function docBlock(?string $class, string $name): DocBlock
    {
        $texts = $this->container->make('config')->get('mcp-docs.descriptions', []);
        $texts = is_array($texts) ? $texts : [];

        $text = ($class !== null ? ($texts[$class] ?? null) : null) ?? $texts[$name] ?? null;

        if (is_string($text) || is_array($text)) {
            $docBlock = DocBlock::fromText($text);

            if (! $docBlock->isEmpty()) {
                return $docBlock;
            }
        }

        return $class === null ? DocBlock::empty() : DocBlock::forClass($class);
    }

    /**
     * Whether text written for the model is documented; anything other than `true` hides it.
     *
     * @return bool
     */
    protected function showsModelText(): bool
    {
        return $this->container->make('config')->get('mcp-docs.model_text', true) === true;
    }

    /**
     * The wire payload without the text written for the model, when that text is not to be documented.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function publicPayload(array $payload): array
    {
        return $this->showsModelText() ? $payload : $this->withoutDescriptions($payload);
    }

    /**
     * The payload with the text configured under `mcp-docs.arguments` written over its argument descriptions.
     *
     * Keys are the paths the schema table shows: `a.b` for a nested property, `a[].b` for a property of a list's items.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withArgumentText(string $name, array $payload): array
    {
        $texts = $this->container->make('config')->get('mcp-docs.arguments', []);
        $texts = is_array($texts) ? ($texts[$name] ?? null) : null;

        if (! is_array($texts)) {
            return $payload;
        }

        foreach ($texts as $path => $text) {
            if (! is_string($path) || ! is_string($text) || $text === '') {
                continue;
            }

            if (is_array($payload['inputSchema'] ?? null)) {
                $payload['inputSchema'] = $this->describeProperty($payload['inputSchema'], $path, $text);
            }

            foreach (is_array($payload['arguments'] ?? null) ? $payload['arguments'] : [] as $index => $argument) {
                if (is_array($argument) && ($argument['name'] ?? null) === $path) {
                    $payload['arguments'][$index]['description'] = $text;
                }
            }
        }

        return $payload;
    }

    /**
     * Sets the description of the schema property at a table path; a path the schema does not hold leaves it unchanged.
     *
     * @param  array<string, mixed>  $schema
     * @param  string  $path
     * @param  string  $text
     * @return array<string, mixed>
     */
    protected function describeProperty(array $schema, string $path, string $text): array
    {
        if (preg_match('/^([^.\[]+)(\[\])?(?:\.(.+))?$/', $path, $parts) !== 1) {
            return $schema;
        }

        $name = $parts[1];
        $rest = $parts[3] ?? '';

        if (! is_array($schema['properties'][$name] ?? null)) {
            return $schema;
        }

        if ($rest === '') {
            $schema['properties'][$name]['description'] = $text;

            return $schema;
        }

        if (($parts[2] ?? '') === '[]') {
            if (is_array($schema['properties'][$name]['items'] ?? null)) {
                $schema['properties'][$name]['items'] = $this->describeProperty($schema['properties'][$name]['items'], $rest, $text);
            }

            return $schema;
        }

        $schema['properties'][$name] = $this->describeProperty($schema['properties'][$name], $rest, $text);

        return $schema;
    }

    /**
     * Drops every string `description` key at any depth; a property named "description" maps to an array and stays.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    protected function withoutDescriptions(array $value): array
    {
        if (is_string($value['description'] ?? null)) {
            unset($value['description']);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->withoutDescriptions($item);
            }
        }

        return $value;
    }

    /**
     * The primitives the server context registers for one kind, recording a warning when they cannot be resolved.
     *
     * @param  ServerContext  $context
     * @param  string  $contextMethod
     * @param  list<string>  $warnings
     * @return list<Primitive>
     */
    protected function resolvePrimitives(ServerContext $context, string $contextMethod, array &$warnings): array
    {
        try {
            return $context->{$contextMethod}()->values()->all();
        } catch (Throwable $e) {
            $warnings[] = "Could not resolve [{$contextMethod}]: {$e->getMessage()}";

            return [];
        }
    }

    /**
     * Runs the callback with the collecting user signed in, restoring the previous user afterwards.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withUser(callable $callback): mixed
    {
        if ($this->user === null) {
            return $callback();
        }

        $guard = $this->container->make('auth')->guard();
        $previous = $guard->user();

        $guard->setUser($this->user);

        try {
            return $callback();
        } finally {
            $previous === null ? $guard->forgetUser() : $guard->setUser($previous);
        }
    }
}
