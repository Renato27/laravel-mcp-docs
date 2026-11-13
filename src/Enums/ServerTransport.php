<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Enums;

enum ServerTransport: string
{
    /** A server reached over HTTP at a URL. */
    case Http = 'http';

    /** A local server started as a process and spoken to over standard input and output. */
    case Stdio = 'stdio';

    /**
     * The transport's human-readable name.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Http => 'HTTP',
            self::Stdio => 'Local (STDIO)',
        };
    }
}
