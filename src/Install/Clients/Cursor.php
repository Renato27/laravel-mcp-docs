<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class Cursor extends Client
{
    public function key(): string
    {
        return 'cursor';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'Cursor';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://cursor.com/docs/mcp';
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
     * The project-scoped `.cursor/mcp.json` entry; only the global file moves between platforms.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $path = '.cursor/mcp.json';
        $notes = ['Use '.$this->globalFile($os).' instead to make the server available in every project. Committing `.cursor/mcp.json` gives the whole team the same tools.'];

        if ($server->registration->isHttp()) {
            return Snippet::json(
                ['mcpServers' => [$this->handle($server) => ['url' => (string) $server->registration->url]]],
                $path,
                $notes,
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::json(
            ['mcpServers' => [$this->handle($server) => ['command' => $command, 'args' => $args]]],
            $path,
            array_values(array_filter([
                ...$notes,
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
        return [
            new InstallStep(
                'create-file',
                'Create the `mcp.json` file',
                'For this project only, create `.cursor/mcp.json` in the project root. To have the server everywhere, create '.$this->globalFile($os).' instead.',
            ),
            new InstallStep(
                'paste',
                'Paste the configuration and save',
                'Use the snippet from the "Config file" tab. If the file already exists, add your server as one more entry under `mcpServers`.',
            ),
            new InstallStep(
                'customize',
                'Open **Customize** in the sidebar',
                'Your server appears in the MCP list there, where it can be toggled on and off.',
            ),
            new InstallStep(
                'verify',
                'Check the logs if it does not appear',
                'Open the Output panel with `'.$os->modifier().' Shift U` and pick **MCP Logs** from the dropdown to see connection errors.',
            ),
        ];
    }

    /**
     * Where the global configuration is written on this platform.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function globalFile(OperatingSystem $os): string
    {
        return '`'.$os->home('~/.cursor/mcp.json').'`';
    }
}
