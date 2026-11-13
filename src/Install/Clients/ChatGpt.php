<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install\Clients;

use LaravelMcpDocs\Collector\ServerDocument;
use LaravelMcpDocs\Install\Client;
use LaravelMcpDocs\Install\InstallStep;
use LaravelMcpDocs\Install\OperatingSystem;
use LaravelMcpDocs\Install\Snippet;

class ChatGpt extends Client
{
    public function key(): string
    {
        return 'chatgpt';
    }

    /**
     * The client's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return 'ChatGPT';
    }

    /**
     * The client's own MCP setup documentation.
     *
     * @param  ServerDocument  $server
     * @return string|null
     */
    public function docsUrl(ServerDocument $server): ?string
    {
        return 'https://developers.openai.com/api/docs/guides/developer-mode';
    }

    /**
     * Why ChatGPT on the web cannot reach this server: it only reaches remote servers, so a STDIO one has no route.
     *
     * @param  ServerDocument  $server
     * @param  OperatingSystem  $os
     * @return string|null
     */
    public function unavailableOn(ServerDocument $server, OperatingSystem $os): ?string
    {
        if ($server->registration->isHttp()) {
            return null;
        }

        return 'ChatGPT on the web cannot start a process on your machine, so a local STDIO server cannot be added there. Use the **OpenAI Codex** tab — the Codex CLI, the ChatGPT desktop app and the IDE extension all run local servers — or register this server over HTTP with `Mcp::web()` and add it here as a developer-mode app.';
    }

    /**
     * The server URL to paste into the app dialog in ChatGPT settings.
     *
     * @param  ServerDocument  $server
     * @return Snippet
     */
    public function snippet(ServerDocument $server): Snippet
    {
        return Snippet::text(
            (string) ($server->registration->url ?? $server->endpoint()),
            [
                'There is no config file: this URL is pasted into the app dialog in ChatGPT settings. Follow the "Step by step" tab.',
                'ChatGPT connects from OpenAI\'s servers, not from your browser, so the URL has to resolve on the public internet. A `localhost` or private-network address will not connect.',
            ],
        );
    }

    /**
     * The walkthrough for adding this server as a developer-mode app, empty for a STDIO server.
     *
     * @param  ServerDocument  $server
     * @return list<InstallStep>
     */
    public function steps(ServerDocument $server): array
    {
        if (! $server->registration->isHttp()) {
            return [];
        }

        return [
            new InstallStep(
                'developer-mode',
                'Turn on **Developer mode**',
                'At **chatgpt.com**, open **Settings → Security and login** and enable **Developer mode**. It is available on Plus, Pro, Business, Enterprise and Edu accounts. On a Business, Enterprise or Edu workspace an admin has to allow it first, in the workspace settings.',
            ),
            new InstallStep(
                'create-app',
                'Create the app from **Plugins**',
                'Open **ChatGPT Plugins**, select the **+** button and create a developer-mode app for this server. Paste the URL from the "Config file" tab as the server URL. Both streamable HTTP and SSE are accepted.',
            ),
            new InstallStep(
                'authenticate',
                'Choose how the server authenticates',
                'OAuth — including static credentials, CIMD and dynamic client registration — or no authentication at all. ChatGPT warns you here that a custom app runs third-party code on your behalf.',
            ),
            new InstallStep(
                'enable-in-chat',
                'Enable the app in a conversation',
                'Developer-mode apps are off by default in a new chat. Open the **+** menu in the composer, choose **Developer mode**, and turn this app on for that conversation. Approvals are remembered within one conversation; a new conversation asks again.',
            ),
            new InstallStep(
                'verify',
                'Check the tools came through',
                'The app\'s details page under **Plugins** lists the tools read from the server, and lets you switch individual ones off. If the list is empty or stale, refresh the app there so it re-reads the server.',
            ),
        ];
    }
}
