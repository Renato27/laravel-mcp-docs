<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Contracts\Config\Repository as Config;

class Intro
{
    /**
     * Creates the intro section reader over the application configuration.
     *
     * @param  Config  $config
     * @return void
     */
    public function __construct(protected Config $config)
    {
    }

    /**
     * Whether the intro section is shown.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->get('mcp-docs.intro.enabled', true) !== false;
    }

    /**
     * The section heading, falling back to the given default and then "MCP".
     *
     * @param  string|null  $default
     * @return string
     */
    public function heading(?string $default = null): string
    {
        return $this->get('heading') ?? $default ?? 'MCP';
    }

    /**
     * The opening paragraph, shown larger than the rest.
     *
     * @return string
     */
    public function lede(): string
    {
        return $this->get('lede') ?? 'This page describes how AI assistants connect to this application over the '
            .'**Model Context Protocol**, and exactly what they are able to do once connected.';
    }

    /**
     * Paragraphs describing the documented servers; empty when not set.
     *
     * A single string is split on blank lines into paragraphs.
     *
     * @return list<string> Paragraphs.
     */
    public function overview(): array
    {
        $overview = $this->config->get('mcp-docs.intro.overview');

        if (is_array($overview)) {
            return array_values(array_filter(
                array_map(fn (mixed $p): string => is_string($p) ? trim($p) : '', $overview),
                fn (string $p): bool => $p !== '',
            ));
        }

        if (! is_string($overview) || trim($overview) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R\s*\R/', trim($overview)) ?: []),
            fn (string $p): bool => $p !== '',
        ));
    }

    /**
     * The explanatory cards: the configured ones, the defaults when unset, none for an explicit empty array.
     *
     * @return list<array{title: string, body: string}>
     */
    public function cards(): array
    {
        $cards = $this->config->get('mcp-docs.intro.cards');

        if (! is_array($cards)) {
            return $this->defaultCards();
        }

        $resolved = [];

        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }

            $title = is_string($card['title'] ?? null) ? trim($card['title']) : '';
            $body = is_string($card['body'] ?? null) ? trim($card['body']) : '';

            if ($title !== '' || $body !== '') {
                $resolved[] = ['title' => $title, 'body' => $body];
            }
        }

        return $resolved;
    }

    /**
     * The footnote: the configured text, the default when unset, or null when explicitly disabled.
     *
     * @return string|null
     */
    public function footnote(): ?string
    {
        $footnote = $this->config->get('mcp-docs.intro.footnote', false);

        if (is_string($footnote) && trim($footnote) !== '') {
            return trim($footnote);
        }

        return $footnote === null
            ? null
            : 'Everything on this page is read from the running servers, so it always matches what a client is actually served.';
    }

    /**
     * The package's general explanation of MCP, as cards.
     *
     * @return list<array{title: string, body: string}>
     */
    protected function defaultCards(): array
    {
        return [
            [
                'title' => 'What MCP is',
                'body' => 'A standard way for an AI assistant to call into an application. The assistant is the client; this application is the server.',
            ],
            [
                'title' => 'What a tool is',
                'body' => 'One action the assistant can take, with named inputs. The assistant decides when to call it, so each tool below lists what it does and whether it changes anything.',
            ],
            [
                'title' => 'How to connect',
                'body' => 'Each server below carries a ready-to-paste snippet for the client you use, plus a step-by-step route through its settings if you would rather click.',
            ],
        ];
    }

    /**
     * Reads a trimmed, non-empty string intro setting, or null.
     *
     * @param  string  $key
     * @return string|null
     */
    protected function get(string $key): ?string
    {
        $value = $this->config->get("mcp-docs.intro.{$key}");

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
