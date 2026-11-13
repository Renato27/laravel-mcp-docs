<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class ClaudeCode extends Client
{
    public function key(): string
    {
        return 'claude-code';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'Claude Code';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://code.claude.com/docs/en/mcp';
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
     * The `claude mcp add` command for this server, with a note on the scope it writes to.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $handle = $this->handle($server);
        $scope = 'Servers are added to *local* scope by default — private to you, in '.$this->configFile($os).'. Add `--scope project` to write `.mcp.json` instead, which can be committed for the team.';

        if ($server->registration->isHttp()) {
            return Snippet::shell(
                "claude mcp add --transport http {$handle} {$server->registration->url}",
                [$scope],
            );
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        return Snippet::shell(
            "claude mcp add {$handle} -- {$command} ".implode(' ', $args),
            array_values(array_filter([
                'The `--` separator matters: everything after it is passed to the server untouched.',
                $this->workingDirectoryNote(),
                $os->isWindows() ? $this->windowsShimNote() : null,
                $scope,
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
        $handle = $this->handle($server);

        return [
            new InstallStep(
                'open-project',
                'Open a terminal in the project',
                $os->isWindows()
                    ? 'Claude Code resolves MCP configuration relative to the directory you start it in. Native Windows and WSL each have their own home directory, so each keeps its own `.claude.json` — add the server from whichever one you run `claude` in.'
                    : 'Claude Code resolves MCP configuration relative to the directory you start it in.',
            ),
            new InstallStep(
                'run-add',
                'Run the `claude mcp add` command',
                'Use the command under the "Config file" tab. Claude Code writes the configuration for you — there is no file to create by hand.',
            ),
            new InstallStep(
                'choose-scope',
                'Decide who gets the server',
                'Without a scope flag the server is private to you, in '.$this->configFile($os).'. Pass `--scope project` to write `.mcp.json` in the project root so it can be committed, or `--scope user` to have it in every project.',
            ),
            new InstallStep(
                'approve',
                'Approve the server, then check `/mcp`',
                "Project-scoped servers need the workspace trust dialog accepted first. Run `/mcp` and `{$handle}` should read **Connected**; **Pending approval** means the trust dialog is still outstanding, and **Needs authentication** that the server wants you to sign in. `claude mcp list` reports the same from outside a session.",
            ),
        ];
    }

    /**
     * Where local- and user-scoped servers are written.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function configFile(OperatingSystem $os): string
    {
        return '`'.$os->home('~/.claude.json').'`';
    }
}
