<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

use Illuminate\Contracts\Config\Repository as Config;
use LaravelMcpDocs\Collector\ServerDocument;

abstract class Client
{
    /**
     * The date the walkthroughs were last checked against each client's official documentation.
     */
    public const VERIFIED_ON = '2026-08-31';

    /**
     * Creates the client, reading screenshots and videos from the given configuration.
     *
     * @param  Config  $config
     * @return void
     */
    public function __construct(protected Config $config)
    {
    }

    /**
     * The key this client is configured and selected by.
     *
     * @return string
     */
    abstract public function key(): string;

    /**
     * The client's display name.
     *
     * @return string
     */
    abstract public function label(): string;

    /**
     * The configuration for this client, where it does not depend on the reader's operating system.
     *
     * @param  ServerDocument  $server
     * @return Snippet
     */
    abstract public function snippet(ServerDocument $server): Snippet;

    /**
     * The client's own setup documentation, cited alongside the walkthrough.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return null;
    }

    /**
     * The point-and-click route to the same result, for readers who would rather not touch a config file.
     *
     * Override this when the walkthrough reads the same everywhere, and `stepsOn()` when it does not.
     *
     * @param  ServerDocument  $server
     * @return list<InstallStep>
     */
    public function steps(ServerDocument $server): array
    {
        return [];
    }

    /**
     * The platforms this client runs on at all.
     *
     * @return list<OperatingSystem>
     */
    public function platforms(): array
    {
        return OperatingSystem::cases();
    }

    /**
     * The configuration, as this operating system writes its paths.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        return $this->snippet($server);
    }

    /**
     * The walkthrough, following this operating system's menus and shortcuts.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return list<InstallStep>
     */
    public function stepsOn(ServerDocument $server, OperatingSystem $os): array
    {
        return $this->steps($server);
    }

    /**
     * Why this client cannot reach this server on this platform, and what to use instead, or null when it can.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return string|null
     */
    public function unavailableOn(ServerDocument $server, OperatingSystem $os): ?string
    {
        return null;
    }

    /**
     * The recording configured for this client under `mcp-docs.videos`: a URL,
     * or an array with "src" and optional "poster" and "caption".
     *
     * @return array{src: string, poster: string|null, caption: string|null}|null
     */
    public function video(): ?array
    {
        $configured = $this->config->get('mcp-docs.videos.'.$this->key());

        if (is_string($configured) && $configured !== '') {
            return ['src' => $configured, 'poster' => null, 'caption' => null];
        }

        if (! is_array($configured) || ! is_string($configured['src'] ?? null) || $configured['src'] === '') {
            return null;
        }

        return [
            'src' => $configured['src'],
            'poster' => is_string($configured['poster'] ?? null) ? $configured['poster'] : null,
            'caption' => is_string($configured['caption'] ?? null) ? $configured['caption'] : null,
        ];
    }

    /**
     * The steps with any application-supplied screenshots attached, an OS-specific screenshot winning over a shared one.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem|null  $os
     * @return list<InstallStep>
     */
    public function illustratedSteps(ServerDocument $server, ?OperatingSystem $os = null): array
    {
        $os ??= $this->platforms()[0] ?? OperatingSystem::MacOs;

        return array_map(function (InstallStep $step) use ($os): InstallStep {
            $configured = $this->config->get("mcp-docs.screenshots.{$this->key()}.{$os->value}.{$step->key}")
                ?? $this->config->get("mcp-docs.screenshots.{$this->key()}.{$step->key}");

            if (is_string($configured) && $configured !== '') {
                return $step->withImage($configured);
            }

            if (is_array($configured) && is_string($configured['src'] ?? null)) {
                return $step->withImage($configured['src'], is_string($configured['caption'] ?? null) ? $configured['caption'] : null);
            }

            return $step;
        }, $this->configuredSteps($server, $os) ?? $this->stepsOn($server, $os));
    }

    /**
     * This client's instructions, grouped so platforms that read identically share one tab.
     *
     * @param  ServerDocument  $server
     * @return list<Walkthrough>
     */
    public function walkthroughs(ServerDocument $server): array
    {
        /** @var array<string, Walkthrough> $grouped */
        $grouped = [];

        foreach (OperatingSystem::cases() as $os) {
            $unavailable = in_array($os, $this->platforms(), true)
                ? $this->unavailableOn($server, $os)
                : $this->unsupportedPlatformNote($os);

            $walkthrough = new Walkthrough(
                platforms: [$os],
                snippet: $unavailable === null ? ($this->configuredSnippet($server, $os) ?? $this->snippetOn($server, $os)) : null,
                steps: $unavailable === null ? $this->illustratedSteps($server, $os) : [],
                unavailable: $unavailable,
                configuredSteps: $this->configuredSteps($server, $os) !== null,
            );

            $fingerprint = $walkthrough->fingerprint();

            $grouped[$fingerprint] = isset($grouped[$fingerprint])
                ? $grouped[$fingerprint]->withPlatforms([...$grouped[$fingerprint]->platforms, $os])
                : $walkthrough;
        }

        return array_values($grouped);
    }

    /**
     * The steps the application configured under `mcp-docs.walkthroughs`, or null to keep the client's own.
     *
     * Each step is keyed by its screenshot key and holds a "title" and an optional "body".
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return list<InstallStep>|null
     */
    public function configuredSteps(ServerDocument $server, OperatingSystem $os): ?array
    {
        $configured = $this->configured('steps', $os);

        if (! is_array($configured)) {
            return null;
        }

        $steps = [];

        foreach ($configured as $key => $step) {
            $step = is_string($step) ? ['title' => $step] : $step;

            if (! is_array($step) || ! is_string($step['title'] ?? null)) {
                continue;
            }

            $steps[] = new InstallStep(
                is_string($key) ? $key : 'step-'.(count($steps) + 1),
                $this->fill($step['title'], $server),
                is_string($step['body'] ?? null) ? $this->fill($step['body'], $server) : null,
            );
        }

        return $steps === [] ? null : $steps;
    }

    /**
     * The snippet the application configured under `mcp-docs.walkthroughs`, or null to keep the client's own.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet|null
     */
    public function configuredSnippet(ServerDocument $server, OperatingSystem $os): ?Snippet
    {
        $configured = $this->configured('snippet', $os);

        if (! is_array($configured) || ! is_string($configured['code'] ?? null)) {
            return null;
        }

        return new Snippet(
            language: is_string($configured['language'] ?? null) ? $configured['language'] : 'text',
            code: $this->fill($configured['code'], $server),
            path: is_string($configured['path'] ?? null) ? $configured['path'] : null,
            notes: array_values(array_map(
                fn (string $note): string => $this->fill($note, $server),
                array_filter((array) ($configured['notes'] ?? []), 'is_string'),
            )),
        );
    }

    /**
     * One part of this client's entry under `mcp-docs.walkthroughs`, the operating system's own entry
     * winning over the shared one.
     *
     * @param  string  $part
     * @param  OperatingSystem  $os
     * @return mixed
     */
    protected function configured(string $part, OperatingSystem $os): mixed
    {
        $walkthroughs = $this->config->get('mcp-docs.walkthroughs', []);
        $entry = is_array($walkthroughs) ? ($walkthroughs[$this->key()] ?? null) : null;

        if (! is_array($entry)) {
            return null;
        }

        return (is_array($entry[$os->value] ?? null) ? ($entry[$os->value][$part] ?? null) : null) ?? $entry[$part] ?? null;
    }

    /**
     * Replaces the placeholders a configured walkthrough may use: {url}, {name}, {key} and {command}.
     *
     * @param  string  $text
     * @param  ServerDocument  $server
     * @return string
     */
    protected function fill(string $text, ServerDocument $server): string
    {
        return strtr($text, [
            '{url}' => (string) $server->registration->url,
            '{name}' => $this->handle($server),
            '{key}' => $server->registration->key,
            '{command}' => 'php artisan mcp:start '.$server->registration->key,
        ]);
    }

    /**
     * The default explanation for a platform this client has no build for.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function unsupportedPlatformNote(OperatingSystem $os): string
    {
        return "{$this->label()} has no {$os->label()} build.";
    }

    /**
     * The connection name a client will show for this server.
     *
     * @param  ServerDocument  $server
     * @return string
     */
    protected function handle(ServerDocument $server): string
    {
        return $server->registration->isHttp()
            ? $server->slug()
            : $server->registration->key;
    }

    /**
     * The command and arguments that start a local server over STDIO.
     *
     * @param  ServerDocument  $server
     * @return array{command: string, args: list<string>}
     */
    protected function stdioCommand(ServerDocument $server): array
    {
        return [
            'command' => 'php',
            'args' => ['artisan', 'mcp:start', $server->registration->key],
        ];
    }

    /**
     * The note telling the reader to run a local server from the project root.
     *
     * @return string
     */
    protected function workingDirectoryNote(): string
    {
        return 'Run this from the project root, or set the client\'s working directory to it, so `php artisan` resolves.';
    }

    /**
     * The note that a `.cmd` or `.bat` shim must be wrapped in `cmd /c` on native Windows.
     *
     * @return string
     */
    protected function windowsShimNote(): string
    {
        return 'On native Windows (not WSL), this works as written when `php.exe` is on your `PATH`. A command that resolves to a `.cmd` or `.bat` shim instead has to be wrapped as `cmd /c <command>`, because Windows does not execute those directly.';
    }
}
