<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class ClaudeDesktop extends Client
{
    public function key(): string
    {
        return 'claude-desktop';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'Claude Desktop';
    }

    /**
     * The guide to connecting a remote or a local server, matching how this server is registered.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return $server->registration->isHttp()
            ? 'https://modelcontextprotocol.io/docs/develop/connect-remote-servers'
            : 'https://modelcontextprotocol.io/docs/develop/connect-local-servers';
    }

    /**
     * Why a STDIO server has no route on Linux, where Claude Desktop has no build; null everywhere else.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return string|null
     */
    public function unavailableOn(ServerDocument $server, OperatingSystem $os): ?string
    {
        if ($os !== OperatingSystem::Linux || $server->registration->isHttp()) {
            return null;
        }

        return 'Claude Desktop has no Linux build, and a local STDIO server has to be launched by the app itself — so there is no route to this server through Claude Desktop on Linux. Use **Claude Code**, which does run on Linux, or expose the server over HTTP with `Mcp::web()` and add it to claude.ai as a custom connector.';
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
     * The connector URL for a remote server, or the `claude_desktop_config.json` entry for a local one.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        if ($server->registration->isHttp()) {
            return Snippet::text(
                (string) $server->registration->url,
                [
                    $os === OperatingSystem::Linux
                        ? 'There is no Linux build of Claude Desktop, but custom connectors belong to your account rather than to the app — add this URL at **claude.ai** in your browser and it is there for every Claude client you sign in to.'
                        : 'Claude Desktop connects to HTTP servers natively as a **custom connector**. Paste this URL into the dialog rather than editing a config file — follow the "Step by step" tab.',
                ],
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::json(
            ['mcpServers' => [$this->handle($server) => [
                'command' => $command,
                'args' => $args,
                'cwd' => base_path(),
            ]]],
            'claude_desktop_config.json',
            array_values(array_filter([
                'The file lives at '.$this->configPath($os).'.',
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
     * The connector walkthrough for a remote server, or the config file walkthrough for a local one.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return list<InstallStep>
     */
    public function stepsOn(ServerDocument $server, OperatingSystem $os): array
    {
        return $server->registration->isHttp()
            ? $this->remoteSteps($os)
            : $this->localSteps($os);
    }

    /**
     * The walkthrough for adding a remote server as a custom connector.
     *
     * @param  OperatingSystem  $os
     * @return list<InstallStep>
     */
    protected function remoteSteps(OperatingSystem $os): array
    {
        return [
            new InstallStep(
                'open-settings',
                $os === OperatingSystem::Linux ? 'Open Settings at claude.ai' : 'Open Settings',
                match ($os) {
                    OperatingSystem::MacOs => 'In Claude Desktop, use the **Claude** menu in the macOS menu bar and choose **Settings…**, or press `Cmd ,`.',
                    OperatingSystem::Windows => 'In Claude Desktop, press `Ctrl ,`, or click the menu icon in the top left, hover **File** and choose **Settings**.',
                    OperatingSystem::Linux => 'There is no Linux desktop build, so use Claude in your browser: sign in at **claude.ai**, click your profile icon and choose **Settings**.',
                },
            ),
            new InstallStep(
                'connectors',
                'Click **Connectors** in the sidebar',
                'This lists the connectors you already have and offers to add more.',
            ),
            new InstallStep(
                'add-custom',
                'Choose **Add**, then **Add custom connector**',
                'The **Add** button sits at the top right of the window. Paste the URL from the "Config file" tab into the dialog and confirm with **Add**.',
            ),
            new InstallStep(
                'authenticate',
                'Complete authentication if prompted',
                'If the server is protected, Claude walks you through its sign-in before finishing the connection.',
            ),
            new InstallStep(
                'verify',
                'Check the tools are there',
                'Click **Add files, connectors, and more** at the bottom left of the message box, hover **Connectors** and select the server to see its tools. Tool permissions are managed back in Connectors settings.',
            ),
        ];
    }

    /**
     * The walkthrough for adding a local server through the config file.
     *
     * @param  OperatingSystem  $os
     * @return list<InstallStep>
     */
    protected function localSteps(OperatingSystem $os): array
    {
        return [
            new InstallStep(
                'open-settings',
                'Open Settings',
                $os->isWindows()
                    ? 'Click the menu icon in the top left, hover **File** and choose **Settings** — or just press `Ctrl ,`.'
                    : 'Use the **Claude** menu in the macOS menu bar — not the settings inside the Claude window — and choose **Settings…**, or press `Cmd ,`.',
            ),
            new InstallStep(
                'developer-tab',
                'Go to the **Developer** tab, then **Edit Config**',
                'Claude Desktop opens `claude_desktop_config.json` at '.$this->configPath($os).', creating it if this is your first server.',
            ),
            new InstallStep(
                'paste',
                'Merge in the configuration and save',
                'Use the snippet from the "Config file" tab. If the file already has servers, add yours as one more entry under `mcpServers` rather than replacing the file.',
            ),
            new InstallStep(
                'restart',
                'Quit Claude Desktop completely, then reopen it',
                $os->isWindows()
                    ? 'Configuration is read at launch, so closing the window is not enough — Claude keeps running in the notification area. Right-click its icon there and choose **Quit**, then start it again.'
                    : 'Configuration is read at launch, so closing the window is not enough. Quit properly with `Cmd Q`, or **Claude → Quit**, then reopen it.',
            ),
            new InstallStep(
                'verify',
                'Check the tools are there',
                'Click **Add files, connectors, and more** at the bottom left of the message box, hover **Connectors**, then **Manage connectors** and select the server.',
            ),
        ];
    }

    /**
     * Where Claude Desktop keeps its config on this platform.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function configPath(OperatingSystem $os): string
    {
        return $os->isWindows()
            ? '`%APPDATA%\\Claude\\claude_desktop_config.json`'
            : '`~/Library/Application Support/Claude/claude_desktop_config.json`';
    }
}
