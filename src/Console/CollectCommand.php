<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Console;

use Illuminate\Console\Command;
use LaravelMcpDocs\Collector\McpDocument;
use LaravelMcpDocs\Collector\PrimitiveDocument;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Collector\ServerDocument;

class CollectCommand extends Command
{
    /** The console command signature. */
    protected $signature = 'mcp:docs:collect
                            {--json : Output the collected document as JSON}
                            {--output= : Write the JSON to a file instead of stdout}';

    /** The console command description. */
    protected $description = 'Collect the documentation manifest from every registered MCP server';

    /**
     * Collects the manifest and prints it as JSON or as a summary.
     *
     * @param  ServerCollector  $collector
     * @return int
     */
    public function handle(ServerCollector $collector): int
    {
        $document = $collector->collect();

        if ($this->option('json') || $this->option('output')) {
            return $this->writeJson($document);
        }

        return $this->writeSummary($document);
    }

    /**
     * Writes the manifest as JSON to stdout, or to the `--output` file.
     *
     * @param  McpDocument  $document
     * @return int
     */
    protected function writeJson(McpDocument $document): int
    {
        $json = $document->toJson();

        $path = $this->option('output');

        if (! is_string($path) || $path === '') {
            $this->line($json);

            return self::SUCCESS;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $json.PHP_EOL);

        $this->components->info("Manifest written to [{$path}].");

        return self::SUCCESS;
    }

    /**
     * Prints a summary of every server and the warnings collection raised.
     *
     * @param  McpDocument  $document
     * @return int
     */
    protected function writeSummary(McpDocument $document): int
    {
        if ($document->isEmpty()) {
            $this->components->warn('No MCP servers were discovered. Register one with Mcp::web() or Mcp::local().');
        }

        foreach ($document->servers as $server) {
            $this->summariseServer($server);
        }

        $this->warnings($document->warnings);

        return self::SUCCESS;
    }

    /**
     * Prints one server's identity, endpoint and primitives.
     *
     * @param  ServerDocument  $server
     * @return void
     */
    protected function summariseServer(ServerDocument $server): void
    {
        $this->newLine();
        $this->components->twoColumnDetail(
            "<fg=cyan;options=bold>{$server->name}</> <fg=gray>v{$server->version}</>",
            "<fg=gray>{$server->transport()->label()}</>",
        );
        $this->components->twoColumnDetail('  <fg=gray>endpoint</>', $server->endpoint());
        $this->components->twoColumnDetail('  <fg=gray>protocol</>', $server->protocolVersion);

        $this->listPrimitives('tools', $server->tools);
        $this->listPrimitives('resources', $server->resources);
        $this->listPrimitives('resource templates', $server->resourceTemplates);
        $this->listPrimitives('prompts', $server->prompts);

        $this->warnings($server->warnings);
    }

    /**
     * Prints one kind of primitive with its descriptions; nothing when the list is empty.
     *
     * @param  string  $label
     * @param  list<PrimitiveDocument>  $primitives
     * @return void
     */
    protected function listPrimitives(string $label, array $primitives): void
    {
        if ($primitives === []) {
            return;
        }

        $count = count($primitives);

        $this->newLine();
        $this->line("  <fg=gray>{$label} ({$count})</>");

        foreach ($primitives as $primitive) {
            $suffix = $primitive->hidden ? ' <fg=yellow>[via search]</>' : '';

            $this->components->twoColumnDetail(
                "  {$primitive->name}{$suffix}",
                '<fg=gray>'.($primitive->description() ?? '—').'</>',
            );
        }
    }

    /**
     * Prints each warning.
     *
     * @param  list<string>  $warnings
     * @return void
     */
    protected function warnings(array $warnings): void
    {
        foreach ($warnings as $warning) {
            $this->components->warn($warning);
        }
    }
}
