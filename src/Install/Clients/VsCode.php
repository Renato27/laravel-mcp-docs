<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class VsCode extends Client
{
    public function key(): string
    {
        return 'vscode';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'VS Code';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://code.visualstudio.com/docs/agent-customization/mcp-servers';
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
     * The `.vscode/mcp.json` entry, nested under "servers" with an explicit type.
     *
     * The user-level file is named by the command that opens it, since its path differs per platform and profile.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $path = '.vscode/mcp.json';

        $notes = ['This is the workspace file. For a server in every workspace, run **MCP: Open User Configuration** from the Command Palette and add it to the file that opens.'];

        if ($server->registration->isHttp()) {
            return Snippet::json(
                ['servers' => [$this->handle($server) => [
                    'type' => 'http',
                    'url' => (string) $server->registration->url,
                ]]],
                $path,
                $notes,
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::json(
            ['servers' => [$this->handle($server) => [
                'type' => 'stdio',
                'command' => $command,
                'args' => $args,
            ]]],
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
        $isHttp = $server->registration->isHttp();
        $mac = $os === OperatingSystem::MacOs;

        return [
            new InstallStep(
                'command-palette',
                'Open the Command Palette and run **MCP: Add Server**',
                'Press `'.($mac ? 'Cmd Shift P' : 'Ctrl Shift P').'`.',
            ),
            new InstallStep(
                'choose-type',
                $isHttp ? 'Choose the HTTP server type' : 'Choose the command (stdio) server type',
                $isHttp
                    ? 'VS Code then asks for the server URL. Paste the endpoint shown at the top of this page.'
                    : 'VS Code then asks for the command to run. Enter `php`, with `artisan mcp:start` and the handle as its arguments.',
            ),
            new InstallStep(
                'choose-scope',
                'Pick where to save it',
                'Choose **Workspace** to write `.vscode/mcp.json` alongside the project, or **Global** to write it into your user profile. **MCP: Open User Configuration** reopens the global file later, wherever your profile keeps it.',
            ),
            new InstallStep(
                'trust',
                'Confirm trust when prompted',
                'VS Code asks before starting a server it has not run before.',
            ),
            new InstallStep(
                'verify',
                'Check the tools in Chat',
                'Open Chat with `'.($mac ? 'Ctrl Cmd I' : 'Ctrl Alt I').'`, switch it to **Agent** mode and select **Configure Tools** to see the server\'s tools and toggle them. **MCP: List Servers** shows the same servers with their status.',
            ),
        ];
    }
}
