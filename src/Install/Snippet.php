<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

class Snippet
{
    /**
     * Creates a ready-to-copy piece of configuration for one client.
     *
     * @param  string  $language  Syntax hint: "json", "bash" or "text".
     * @param  string  $code
     * @param  string|null  $path
     * @param  list<string>  $notes  Anything the reader must know before pasting.
     * @return void
     */
    public function __construct(
        public readonly string $language,
        public readonly string $code,
        public readonly ?string $path = null,
        public readonly array $notes = [],
    ) {
    }

    /**
     * A JSON snippet encoded from the given configuration.
     *
     * @param  array<string, mixed>  $config
     * @param  string|null  $path
     * @param  list<string>  $notes
     * @return self
     */
    public static function json(array $config, ?string $path = null, array $notes = []): self
    {
        return new self(
            language: 'json',
            code: (string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            path: $path,
            notes: $notes,
        );
    }

    /**
     * A value pasted into a dialog rather than written to a file.
     *
     * @param  string  $value
     * @param  list<string>  $notes
     * @return self
     */
    public static function text(string $value, array $notes = []): self
    {
        return new self(language: 'text', code: $value, notes: $notes);
    }

    /**
     * A shell command to run.
     *
     * @param  string  $command
     * @param  list<string>  $notes
     * @return self
     */
    public static function shell(string $command, array $notes = []): self
    {
        return new self(language: 'bash', code: $command, notes: $notes);
    }
}
