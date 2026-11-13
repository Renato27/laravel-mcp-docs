<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class Zed extends Client
{
    public function key(): string
    {
        return 'zed';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'Zed';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://zed.dev/docs/ai/mcp';
    }

    /**
     * The configuration, as macOS writes it.
     *
     * @param  ServerDocument  $server
     * @return Snippet
     */
    public function snippet(ServerDocument $server): Snippet
    {
        return $this->snippetOn($server, OperatingSystem::MacOs);
    }

    /**
     * The `context_servers` entry for Zed's `settings.json`, taking a url directly for a remote server.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $path = $this->settingsPath($os);

        if ($server->registration->isHttp()) {
            return Snippet::json(
                ['context_servers' => [$this->handle($server) => [
                    'source' => 'custom',
                    'url' => (string) $server->registration->url,
                ]]],
                $path,
                ['Add a `headers` object alongside `url` if the server needs authentication.'],
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::json(
            ['context_servers' => [$this->handle($server) => [
                'source' => 'custom',
                'command' => $command,
                'args' => $args,
                'env' => (object) [],
            ]]],
            $path,
            array_values(array_filter([
                $this->workingDirectoryNote(),
                $os->isWindows() ? $this->windowsShimNote() : null,
            ])),
        );
    }

    /**
     * The walkthrough, as it reads on macOS.
     *
     * @param  ServerDocument  $server
     * @return list<InstallStep>
     */
    public function steps(ServerDocument $server): array
    {
        return $this->stepsOn($server, OperatingSystem::MacOs);
    }

    /**
     * The point-and-click walkthrough, following this operating system's menus and shortcuts.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return list<InstallStep>
     */
    public function stepsOn(ServerDocument $server, OperatingSystem $os): array
    {
        $isHttp = $server->registration->isHttp();

        return [
            new InstallStep(
                'open-settings',
                'Open **Settings → AI → MCP Servers**',
                'Zed calls MCP servers *context servers*. Running `agent: open settings` from the command palette and picking **MCP Servers** gets to the same page.',
            ),
            new InstallStep(
                'add-server',
                'Click **Add Server** in the page header',
                $isHttp
                    ? 'Then choose **Add Remote Server**, since this server is reached over HTTP.'
                    : 'Then choose **Add Local Server**, since this server runs as a command on your machine.',
            ),
            new InstallStep(
                'paste',
                'Paste the configuration and save',
                'Zed opens `settings.json` at the right place — it lives at `'.$this->settingsPath($os).'`. Merge the snippet from the "Config file" tab into the `context_servers` object.',
            ),
            new InstallStep(
                'verify',
                'Check the indicator dot',
                'Back on the MCP Servers page, a green dot next to the server means **Server is active**. Any other colour points at a configuration or connection problem — hover it to read why.',
            ),
        ];
    }

    /**
     * Where Zed keeps its settings: the XDG layout on macOS and Linux, %APPDATA% on Windows.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function settingsPath(OperatingSystem $os): string
    {
        return $os->isWindows()
            ? '%APPDATA%\\Zed\\settings.json'
            : '~/.config/zed/settings.json';
    }
}
