<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Http\Controllers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\View;
use LaravelMcpDocs\Collector\ServerCollector;
use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\ClientRegistry;
use LaravelMcpDocs\Support\Branding;
use LaravelMcpDocs\Support\DocsAccess;
use LaravelMcpDocs\Support\Intro;

class DocsController
{
    /**
     * Creates the controller with the collector and the page's configuration services.
     *
     * @param  ServerCollector  $collector
     * @param  ClientRegistry  $clients
     * @param  Branding  $branding
     * @param  DocsAccess  $access
     * @param  Intro  $intro
     * @param  Config  $config
     */
    public function __construct(
        protected ServerCollector $collector,
        protected ClientRegistry $clients,
        protected Branding $branding,
        protected DocsAccess $access,
        protected Intro $intro,
        protected Config $config,
    ) {
    }

    /**
     * Renders the documentation page with a walkthrough per server and client.
     *
     * The transport shapes each server's configuration and the operating system each client's paths. Any
     * primitives default other than `closed` opens the cards.
     *
     * @return View
     */
    public function __invoke(): View
    {
        $document = $this->collector->collect();
        $clients = $this->clients->enabled();

        $walkthroughs = [];

        foreach ($document->servers as $server) {
            foreach ($clients as $client) {
                $walkthroughs[$server->slug()][$client->key()] = $client->walkthroughs($server);
            }
        }

        $theme = $this->config->get('mcp-docs.theme.default', 'system');

        $primitivesOpen = $this->config->get('mcp-docs.primitives.default', 'open') !== 'closed';

        return view('mcp-docs::docs', [
            'branding' => $this->branding,
            'access' => $this->access,
            'intro' => $this->intro,
            'title' => $this->branding->name(),
            'theme' => in_array($theme, ['light', 'dark'], true) ? $theme : 'system',
            'switcher' => $this->config->get('mcp-docs.theme.switcher', true) === true,
            'primitivesOpen' => $primitivesOpen,
            'css' => $this->asset('docs.css'),
            'js' => $this->asset('docs.js'),
            'document' => $document,
            'clients' => $clients,
            'walkthroughs' => $walkthroughs,
        ]);
    }

    /**
     * The contents of a bundled asset, inlined so the page works offline and in one request.
     *
     * @param  string  $file
     * @return string
     */
    protected function asset(string $file): string
    {
        return (string) file_get_contents(__DIR__.'/../../../resources/assets/'.$file);
    }

    /**
     * The raw manifest, for tooling that would rather not scrape the page.
     *
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        return $this->collector->collect()->toArray();
    }
}
