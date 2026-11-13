<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Contracts\Container\Container;
use Laravel\Mcp\Enums\MetaKey;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\ServerContext;
use RuntimeException;

class ProtocolClient
{
    /**
     * The server instance being spoken to.
     */
    protected Server $server;

    /**
     * The in-process transport that captures the server's replies.
     */
    protected RecordingTransport $transport;

    /**
     * The server's context, read once after it starts.
     */
    protected ServerContext $context;

    /**
     * The protocol version sent with every request.
     */
    protected string $protocolVersion;

    /**
     * The last JSON-RPC request id issued.
     */
    protected int $id = 0;

    /**
     * Starts the server in-process and settles on the newest protocol version it accepts.
     *
     * @param  Container  $container
     * @param  class-string<Server>  $serverClass
     * @return void
     *
     * @throws RuntimeException
     */
    public function __construct(Container $container, string $serverClass)
    {
        $this->transport = new RecordingTransport;

        $this->server = $container->make($serverClass, ['transport' => $this->transport]);
        $this->server->start();

        $this->context = $this->server->createContext();

        $versions = $this->context->supportedProtocolVersions;

        if ($versions === []) {
            throw new RuntimeException("The server [{$serverClass}] does not support any protocol version.");
        }

        $this->protocolVersion = (string) $versions[0];
    }

    /**
     * The server's context.
     *
     * @return ServerContext
     */
    public function context(): ServerContext
    {
        return $this->context;
    }

    /**
     * The protocol version this client speaks.
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Issue a single request and return its `result` payload.
     *
     * @param  string  $method
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function request(string $method, array $params = []): array
    {
        $this->transport->flush();

        $this->server->handle((string) json_encode([
            'jsonrpc' => '2.0',
            'id' => ++$this->id,
            'method' => $method,
            'params' => [
                ...$params,
                ...$this->meta(),
            ],
        ]));

        $messages = $this->transport->flush();

        if ($messages === []) {
            throw new RuntimeException("The server returned no response for [{$method}].");
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) end($messages), true, flags: JSON_THROW_ON_ERROR);

        if (isset($decoded['error'])) {
            $message = $decoded['error']['message'] ?? 'Unknown error';

            throw new RuntimeException("The server rejected [{$method}]: {$message}");
        }

        return is_array($decoded['result'] ?? null) ? $decoded['result'] : [];
    }

    /**
     * The `_meta` block a client of this protocol version would send; empty when `MetaKey` does not exist.
     *
     * @return array<string, mixed>
     */
    protected function meta(): array
    {
        if (! enum_exists(MetaKey::class)) {
            return [];
        }

        return [
            '_meta' => [
                MetaKey::PROTOCOL_VERSION->value => $this->protocolVersion,
                MetaKey::CLIENT_CAPABILITIES->value => (object) [],
            ],
        ];
    }

    /**
     * Page through a list method until the server stops handing back a new cursor.
     *
     * @param  string  $method
     * @param  string  $key
     * @return list<array<string, mixed>>
     */
    public function list(string $method, string $key): array
    {
        $items = [];
        $cursor = null;
        $seen = [];

        do {
            $result = $this->request($method, $cursor === null ? [] : ['cursor' => $cursor]);

            foreach ($result[$key] ?? [] as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }

            $cursor = is_string($result['nextCursor'] ?? null) ? $result['nextCursor'] : null;

            if ($cursor !== null && isset($seen[$cursor])) {
                break;
            }

            if ($cursor !== null) {
                $seen[$cursor] = true;
            }
        } while ($cursor !== null);

        return $items;
    }
}
