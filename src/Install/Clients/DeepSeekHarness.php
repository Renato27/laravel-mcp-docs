<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class DeepSeekHarness extends Client
{
    public function key(): string
    {
        return 'deepseek';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'DeepSeek Harness';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://github.com/deepseek-ai/deepseek-harness/blob/master/packages/mcp/mcp-client/README.md';
    }

    /**
     * The `cordis.yml` plugin entry for this server; the harness mounts one plugin instance per MCP server.
     *
     * `serverName` namespaces the tools the model sees, so it is limited to [A-Za-z0-9_-].
     *
     * @param  ServerDocument  $server
     * @return Snippet
     */
    public function snippet(ServerDocument $server): Snippet
    {
        $name = (string) preg_replace('/[^A-Za-z0-9_-]/', '-', $this->handle($server));
        $path = 'cordis.yml';

        $notes = ["Tools reach the model as `mcp__{$name}__<tool>`, so `serverName` must be unique across your mounted servers."];

        if ($server->registration->isHttp()) {
            $yaml = <<<YAML
            - id: mcp-{$name}
              name: '@deepseek-ai/dsh-mcp-client'
              config:
                serverName: {$name}
                transport: streamable-http
                url: {$server->registration->url}
            YAML;

            return new Snippet('yaml', $yaml, $path, [
                ...$notes,
                'Add a `headers` map alongside `url` if the server needs authentication.',
            ]);
        }

        ['command' => $command, 'args' => $args] = $this->stdioCommand($server);

        $arguments = implode(', ', array_map(fn (string $arg): string => "'".$arg."'", $args));
        $cwd = base_path();

        $yaml = <<<YAML
        - id: mcp-{$name}
          name: '@deepseek-ai/dsh-mcp-client'
          config:
            serverName: {$name}
            transport: stdio
            command: {$command}
            args: [{$arguments}]
            cwd: {$cwd}
        YAML;

        return new Snippet('yaml', $yaml, $path, [
            ...$notes,
            '`cwd` points the child process at the project so `php artisan` resolves.',
        ]);
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
                'open-config',
                'Open your harness `cordis.yml`',
                'This is the plugin list the harness loads. To patch a profile you did not author, add the entry through `'.$os->home('~/.dsh/profiles/').'<profile>'.($os->isWindows() ? '\\' : '/').'cordis.patch.yml` instead.',
            ),
            new InstallStep(
                'add-plugin',
                'Add one `@deepseek-ai/dsh-mcp-client` entry',
                'The harness mounts one plugin instance per MCP server — paste the entry from the "Config file" tab alongside your other plugins rather than replacing them.',
            ),
            new InstallStep(
                'name-server',
                'Give it a unique `serverName`',
                'It namespaces this server\'s tools for the model. A duplicate `serverName` across live instances makes the later plugin instance fail to load.',
            ),
            new InstallStep(
                'verify',
                'Reload and check the tools appeared',
                'Editing the entry hot-swaps the connection without restarting the process. The tools show up as `mcp__<serverName>__<tool>`; `dsh web --dump-config` shows the resolved configuration if one does not.',
            ),
        ];
    }
}
