<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Closure;
use Laravel\Mcp\Server\Contracts\Transport;
use LogicException;

class RecordingTransport implements Transport
{
    /**
     * Messages the server sent since the last flush.
     *
     * @var list<string>
     */
    protected array $messages = [];

    /**
     * The handler the server registered for incoming messages.
     */
    protected ?Closure $handler = null;

    /**
     * Stores the handler the server registers for incoming messages.
     *
     * @param  Closure  $handler
     * @return void
     */
    public function onReceive(Closure $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * Records a message the server sent; the session id is accepted and ignored.
     *
     * @param  string  $message
     * @param  string|null  $sessionId
     * @return void
     */
    public function send(string $message, ?string $sessionId = null): void
    {
        $this->messages[] = $message;
    }

    /**
     * The session this transport speaks for; documentation has none.
     *
     * @return string|null
     */
    public function sessionId(): ?string
    {
        return null;
    }

    /**
     * Runs a streamed response eagerly, so its messages are recorded alongside every other reply.
     *
     * @param  Closure  $stream
     * @return void
     */
    public function stream(Closure $stream): void
    {
        $stream();
    }

    /**
     * Refuses to run: this transport is documentation-only.
     *
     * @return never
     *
     * @throws LogicException
     */
    public function run(): never
    {
        throw new LogicException('The recording transport is documentation-only and cannot be run.');
    }

    /**
     * The messages sent since the last flush, oldest first.
     *
     * @return list<string>
     */
    public function flush(): array
    {
        $messages = $this->messages;

        $this->messages = [];

        return $messages;
    }
}
