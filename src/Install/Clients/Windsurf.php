<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class Windsurf extends Client
{
    public function key(): string
    {
        return 'windsurf';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'Windsurf';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://docs.devin.ai/desktop/cascade/mcp';
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
     * The `mcp_config.json` entry, with a remote endpoint spelled "serverUrl".
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $path = $this->configPath($os);

        if ($server->registration->isHttp()) {
            return Snippet::json(
                ['mcpServers' => [$this->handle($server) => ['serverUrl' => (string) $server->registration->url]]],
                $path,
                ['`serverUrl` is the spelling Windsurf documents for remote servers; a plain `url` is also accepted. Add a `headers` object alongside it if the server needs authentication.'],
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::json(
            ['mcpServers' => [$this->handle($server) => ['command' => $command, 'args' => $args]]],
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
        return [
            new InstallStep(
                'open-cascade',
                'Open the Cascade panel and click the **MCPs** icon',
                'It sits in the menu at the top right of the panel. The same section is reachable through **Devin Settings → Cascade → MCP Servers**.',
            ),
            new InstallStep(
                'open-config',
                'Open the raw configuration',
                'Windsurf edits `'.$this->configPath($os).'`, creating it if this is the first server you add.',
            ),
            new InstallStep(
                'paste',
                'Merge in the configuration and save',
                'Use the snippet from the "Config file" tab, adding your server under `mcpServers` alongside any existing ones.',
            ),
            new InstallStep(
                'verify',
                'Refresh the server list',
                'Back in the MCPs panel the server should be listed with its tools; restart Windsurf if it is not picked up. Toggle individual tools there if you do not want all of them enabled.',
            ),
        ];
    }

    /**
     * Where Windsurf keeps its MCP configuration on this platform.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function configPath(OperatingSystem $os): string
    {
        return $os->home('~/.codeium/windsurf/mcp_config.json');
    }
}
