<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Console;

use Illuminate\Console\Command;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\ClientRegistry;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Walkthrough;

class StepsCommand extends Command
{
    /** The console command signature. */
    protected $signature = 'mcp:docs:steps
        {--client= : Only show steps for one client}
        {--os= : Only show steps for one platform: macos, windows or linux}';

    /** The console command description. */
    protected $description = 'List the walkthrough steps you can attach screenshots to';

    /**
     * Lists the screenshot keys of every enabled client's walkthrough, against the first server.
     *
     * Steps vary by transport, so the first server is used. Keys are platform-scoped when the client has more
     * than one walkthrough, whatever `--os` shows, so filtering never renames a key.
     *
     * @param  ServerCollector  $collector
     * @param  ClientRegistry  $registry
     * @return int
     */
    public function handle(ServerCollector $collector, ClientRegistry $registry): int
    {
        $document = $collector->collect();

        if ($document->isEmpty()) {
            $this->components->warn('No MCP servers were discovered, so there are no walkthroughs to list.');

            return self::SUCCESS;
        }

        $server = $document->servers[0];
        $only = $this->option('client');
        $os = is_string($this->option('os')) ? OperatingSystem::tryFrom(strtolower($this->option('os'))) : null;

        if (is_string($this->option('os')) && $os === null) {
            $this->components->error('Unknown --os. Use one of: '.implode(', ', array_column(OperatingSystem::cases(), 'value')).'.');

            return self::FAILURE;
        }

        $this->components->info("Screenshot keys, from [{$server->name}] over {$server->transport()->label()}.");

        foreach ($registry->enabled() as $client) {
            if (is_string($only) && $only !== '' && $client->key() !== $only) {
                continue;
            }

            $all = $client->walkthroughs($server);

            $perPlatform = count($all) > 1;

            $walkthroughs = array_values(array_filter(
                $all,
                fn (Walkthrough $walkthrough): bool => $os === null || $walkthrough->covers($os),
            ));

            if ($walkthroughs === []) {
                continue;
            }

            $this->newLine();
            $this->line("  <fg=cyan;options=bold>{$client->label()}</> <fg=gray>({$client->key()})</>");

            $perPlatform
                ? $this->perPlatform($client, $walkthroughs)
                : $this->single($client, $walkthroughs[0]);
        }

        return self::SUCCESS;
    }

    /**
     * A client whose walkthrough reads the same everywhere: one list of keys.
     *
     * @param  Client  $client
     * @param  Walkthrough  $walkthrough
     * @return void
     */
    protected function single(Client $client, Walkthrough $walkthrough): void
    {
        if ($walkthrough->unavailable !== null) {
            $this->line('    <fg=yellow>No walkthrough:</> <fg=gray>'.$this->plain($walkthrough->unavailable).'</>');

            return;
        }

        foreach ($walkthrough->steps as $step) {
            $this->key("mcp-docs.screenshots.{$client->key()}.{$step->key}", $step);
        }
    }

    /**
     * A client whose walkthrough differs by platform: one list of keys per tab.
     *
     * A tab for a single platform takes the nested key, which overrides the bare one; a tab covering several
     * platforms takes the bare key, which applies to all of them.
     *
     * @param  Client  $client
     * @param  list<Walkthrough>  $walkthroughs
     * @return void
     */
    protected function perPlatform(Client $client, array $walkthroughs): void
    {
        foreach ($walkthroughs as $walkthrough) {
            $this->line("    <options=bold>{$walkthrough->label()}</>");

            if ($walkthrough->unavailable !== null) {
                $this->line('      <fg=yellow>No walkthrough:</> <fg=gray>'.$this->plain($walkthrough->unavailable).'</>');

                continue;
            }

            $scope = count($walkthrough->platforms) === 1
                ? $walkthrough->platforms[0]->value.'.'
                : '';

            foreach ($walkthrough->steps as $step) {
                $this->key("mcp-docs.screenshots.{$client->key()}.{$scope}{$step->key}", $step);
            }
        }
    }

    /**
     * Prints one screenshot key beside its step's title.
     *
     * @param  string  $key
     * @param  InstallStep  $step
     * @return void
     */
    protected function key(string $key, InstallStep $step): void
    {
        $this->components->twoColumnDetail("  {$key}", '<fg=gray>'.$this->plain($step->title).'</>');
    }

    /**
     * The text without Markdown emphasis and code marks.
     *
     * @param  string  $text
     * @return string
     */
    protected function plain(string $text): string
    {
        return str_replace(['*', '`'], '', $text);
    }
}
