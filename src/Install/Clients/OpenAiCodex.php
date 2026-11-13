<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class OpenAiCodex extends Client
{
    public function key(): string
    {
        return 'codex';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'OpenAI Codex';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://learn.chatgpt.com/docs/extend/mcp';
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
     * The TOML entry for `~/.codex/config.toml`, the one file the Codex CLI, the ChatGPT desktop app and the IDE extension share.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return Snippet
     */
    public function snippetOn(ServerDocument $server, OperatingSystem $os): Snippet
    {
        $handle = $this->handle($server);
        $path = $os->home('~/.codex/config.toml');

        $shared = 'The Codex CLI, the ChatGPT desktop app and the IDE extension all read this file, so adding the server once adds it to all three.';

        if ($server->registration->isHttp()) {
            $toml = <<<TOML
            [mcp_servers.{$handle}]
            url = "{$server->registration->url}"
            TOML;

            return new Snippet('toml', $toml, $path, [
                'Or let the CLI write it: `codex mcp add '.$handle.' --url '.$server->registration->url.'`.',
                'Use `.codex/config.toml` in the project instead to scope the server to it — Codex reads that only in trusted projects.',
                $shared,
            ]);
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        $arguments = implode(', ', array_map(fn (string $arg): string => '"'.$arg.'"', $args));

        $toml = <<<TOML
        [mcp_servers.{$handle}]
        command = "{$command}"
        args = [{$arguments}]
        TOML;

        return new Snippet('toml', $toml, $path, array_values(array_filter([
            'Or let the CLI write it: `codex mcp add '.$handle.' -- '.$command.' '.implode(' ', $args).'`.',
            $this->workingDirectoryNote(),
            $os->isWindows() ? $this->windowsShimNote() : null,
            $shared,
        ])));
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
                'Open **Settings**, then **MCP servers**',
                'In the **ChatGPT desktop app**, or through the gear menu in the Codex IDE extension. If you use the Codex CLI instead, run the `codex mcp add` command from the "Config file" tab and skip to the last step — all three write the same `config.toml`.',
            ),
            new InstallStep(
                'add-server',
                'Select **Add server**',
                'Codex asks for a name and a transport before anything else.',
            ),
            new InstallStep(
                'choose-type',
                $isHttp ? 'Choose **Streamable HTTP**' : 'Choose **STDIO**',
                $isHttp
                    ? 'Then paste the endpoint shown at the top of this page as the server URL.'
                    : 'Then give it the command to run: `php`, with `artisan mcp:start` and the handle as arguments.',
            ),
            new InstallStep(
                'restart',
                'Save, then select **Restart**',
                'Codex reconnects its servers on restart. Editing '.$this->configFile($os).' by hand needs the same restart.',
            ),
            new InstallStep(
                'verify',
                'Confirm it registered',
                'Type `/mcp` in the composer to list the connected servers and see which ones still need to authenticate. From the CLI, `codex mcp list` reports the same.',
            ),
        ];
    }

    /**
     * Where the shared Codex configuration is written on this platform.
     *
     * @param  OperatingSystem  $os
     * @return string
     */
    protected function configFile(OperatingSystem $os): string
    {
        return '`'.$os->home('~/.codex/config.toml').'`';
    }
}
