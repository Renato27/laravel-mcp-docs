<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use LaravelMcpDocs\Install\Clients\ChatGpt;
use LaravelMcpDocs\Install\Clients\ClaudeCode;
use LaravelMcpDocs\Install\Clients\ClaudeDesktop;
use LaravelMcpDocs\Install\Clients\Cursor;
use LaravelMcpDocs\Install\Clients\DeepSeekHarness;
use LaravelMcpDocs\Install\Clients\OpenAiCodex;
use LaravelMcpDocs\Install\Clients\VsCode;
use LaravelMcpDocs\Install\Clients\Windsurf;
use LaravelMcpDocs\Install\Clients\Zed;

class ClientRegistry
{
    /**
     * The clients the documentation page can offer, keyed by configuration key.
     *
     * @var array<string, class-string<Client>>
     */
    protected array $clients = [
        'claude-code' => ClaudeCode::class,
        'claude-desktop' => ClaudeDesktop::class,
        'chatgpt' => ChatGpt::class,
        'codex' => OpenAiCodex::class,
        'cursor' => Cursor::class,
        'vscode' => VsCode::class,
        'zed' => Zed::class,
        'windsurf' => Windsurf::class,
        'deepseek' => DeepSeekHarness::class,
    ];

    /**
     * Creates the registry over the container that builds clients and the package configuration.
     *
     * @param  Container  $container
     * @param  Config  $config
     * @return void
     */
    public function __construct(
        protected Container $container,
        protected Config $config,
    ) {
    }

    /**
     * Adds or replaces a client under the given key.
     *
     * @param  string  $key
     * @param  class-string<Client>  $client
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public function register(string $key, string $client): static
    {
        if (! is_subclass_of($client, Client::class)) {
            throw new InvalidArgumentException("[{$client}] must extend ".Client::class.'.');
        }

        $this->clients[$key] = $client;

        return $this;
    }

    /**
     * The enabled clients, in the order the configuration lists them.
     *
     * @return list<Client>
     */
    public function enabled(): array
    {
        $keys = $this->config->get('mcp-docs.clients', array_keys($this->clients));

        if (! is_array($keys) || $keys === []) {
            $keys = array_keys($this->clients);
        }

        $clients = [];

        foreach ($keys as $key) {
            if (isset($this->clients[$key])) {
                $clients[] = $this->container->make($this->clients[$key], ['config' => $this->config]);
            }
        }

        return $clients;
    }
}
