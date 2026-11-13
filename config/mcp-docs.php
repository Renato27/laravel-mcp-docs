<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Route
    |--------------------------------------------------------------------------
    |
    | Where the documentation page is served. Set "enabled" to false to keep
    | the page out of an environment entirely; the artisan commands keep
    | working either way.
    |
    */

    'route' => [
        'enabled' => env('MCP_DOCS_ENABLED', true),
        'uri' => env('MCP_DOCS_URI', 'mcp/docs'),
        'middleware' => ['web'],
        'name' => 'mcp-docs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    |
    | This page lists everything an AI assistant can do to your application, so
    | it is treated as internal by default.
    |
    |   "public"     - anyone who can reach the URL can read it.
    |   "restricted" - only the addresses in "allowed_ips" can; everyone else
    |                  gets a 403. This is the default, and anything other than
    |                  the exact string "public" is treated as restricted.
    |
    | "allowed_ips" accepts single addresses and CIDR ranges, IPv4 and IPv6.
    | An empty list under "restricted" allows nobody.
    |
    | Behind a load balancer or CDN, configure Laravel's TrustProxies first --
    | otherwise every request appears to come from the proxy.
    |
    */

    'access' => [
        'mode' => env('MCP_DOCS_ACCESS', 'restricted'),

        'allowed_ips' => [
            '127.0.0.1',
            '::1',
            // '10.0.0.0/8',
            // '192.168.0.0/16',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Everything here is optional. "logo" accepts a URL, a path under public/,
    | a data URI, or an absolute path to an SVG on disk -- an SVG is inlined so
    | it inherits the page colours. Set "logo_dark" when your mark needs a
    | different treatment on a dark background.
    |
    */

    'branding' => [
        'name' => env('MCP_DOCS_TITLE', config('app.name').' MCP'),
        'tagline' => null,
        'logo' => null,
        'logo_dark' => null,
        'favicon' => null,
        'accent' => null,
        'accent_dark' => null,

        // Shown under the brand in the sidebar, as label => url.
        'links' => [
            // 'Support' => 'https://example.com/support',
        ],

        'footer' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Introduction
    |--------------------------------------------------------------------------
    |
    | The explanatory section at the top of the page. The defaults explain MCP
    | to someone meeting it for the first time; replace them to describe your
    | own servers instead.
    |
    | "overview" accepts a string or a list of paragraphs and renders under the
    | lede -- it is the place for what these particular servers are for, who
    | should connect, and anything a reader needs to know before they do.
    |
    | "cards" replaces the three explainer cards. An empty array removes them.
    | Set "enabled" to false to drop the whole section, or "footnote" to null
    | to drop just the closing line. Backticks, **bold** and _italic_ render.
    |
    */

    'intro' => [
        'enabled' => true,

        'heading' => null,
        'lede' => null,

        'overview' => null,
        // 'overview' => [
        //     'These servers give support agents read access to the order book,
        //      plus a guarded path for creating orders on a customer\'s behalf.',
        //     'Access is scoped to your own account: an assistant can only reach
        //      what you can reach in the app itself.',
        // ],

        'cards' => null,
        // 'cards' => [
        //     ['title' => 'Who this is for', 'body' => 'Support agents using an AI assistant.'],
        //     ['title' => 'What it can do', 'body' => 'Look up orders and create them from a cart.'],
        // ],

        'footnote' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | The theme a reader gets before choosing one: "light", "dark" or "system".
    | Set "switcher" to false to remove the control and lock the page to it.
    |
    */

    'theme' => [
        'default' => env('MCP_DOCS_THEME', 'system'),
        'switcher' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Primitive Cards
    |--------------------------------------------------------------------------
    |
    | Whether each tool, resource and prompt starts open or closed on a first
    | visit. "closed" turns a long server into a scannable index of names;
    | "open" shows every schema at once.
    |
    | This decides the first visit only. A reader who opens or closes a card
    | keeps that choice in their own browser from then on, and anything other
    | than the exact string "closed" is treated as open.
    |
    */

    'primitives' => [
        'default' => env('MCP_DOCS_PRIMITIVES', 'open'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Human Descriptions
    |--------------------------------------------------------------------------
    |
    | The prose shown for a server, tool, resource or prompt, keyed by its
    | class or by its name. A string, or a list of paragraphs; the first is
    | the summary. Configured text wins over the class docblock, so use it
    | when classes carry no docblock or carry one not written for the public.
    |
    */

    'descriptions' => [
        // 'search-orders' => 'Find an order by customer, status or reference.',
        // App\Mcp\Servers\OrdersServer::class => ['The order book.', 'Read only.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Argument Descriptions
    |--------------------------------------------------------------------------
    |
    | The text shown for each argument, keyed by tool or prompt name and then
    | by the name the inputs table shows: "filters.city" for a nested field,
    | "items[].sku" for a field of a list. Configured text is shown even when
    | the text written for the model is hidden.
    |
    */

    'arguments' => [
        // 'search-orders' => ['status' => 'Only orders in this status.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text Written for the Model
    |--------------------------------------------------------------------------
    |
    | Server instructions, tool descriptions and argument descriptions are
    | written for the assistant. Set this to false to keep all of it off the
    | page, the JSON manifest and `mcp:docs:collect`. Anything other than
    | true hides it.
    |
    */

    'model_text' => env('MCP_DOCS_MODEL_TEXT', true),

    /*
    |--------------------------------------------------------------------------
    | Walkthrough Overrides
    |--------------------------------------------------------------------------
    |
    | Replace a client's built-in instructions when they no longer match its
    | interface, without waiting for a release. Keyed by client; "snippet"
    | and "steps" each replace the client's own, and either may be nested
    | under "macos", "windows" or "linux" to apply to that platform only.
    |
    | Steps are keyed by their screenshot key. Text may use {url}, {name},
    | {key} and {command}, filled in for each server.
    |
    */

    'walkthroughs' => [
        // 'claude-desktop' => [
        //     'steps' => [
        //         'open-settings' => ['title' => 'Open **Settings**', 'body' => 'Click your name, then Settings.'],
        //         'add-custom' => ['title' => 'Add a custom connector', 'body' => 'Paste `{url}` and save.'],
        //     ],
        //     'windows' => ['steps' => [/* on Windows */]],
        // ],
        //
        // 'zed' => [
        //     'snippet' => ['language' => 'json', 'code' => '{"context_servers": {"{name}": {"url": "{url}"}}}'],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Walkthrough Screenshots
    |--------------------------------------------------------------------------
    |
    | Each client carries a written point-and-click walkthrough. Attach your own
    | screenshots to any step by key; unillustrated steps simply render as text.
    | Run `php artisan mcp:docs:steps` to list every available key.
    |
    | Walkthroughs that differ between operating systems can be illustrated per
    | platform by nesting the steps under "macos", "windows" or "linux". An
    | OS-specific image wins over one attached to the bare step key.
    |
    */

    'screenshots' => [
        // 'cursor' => [
        //     'create-file' => '/img/mcp/cursor-create-file.png',
        //     'customize' => ['src' => '/img/mcp/cursor-customize.png', 'caption' => 'The Customize page in Cursor'],
        // ],
        //
        // 'claude-desktop' => [
        //     'macos' => ['open-settings' => '/img/mcp/claude-settings-macos.png'],
        //     'windows' => ['open-settings' => '/img/mcp/claude-settings-windows.png'],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Walkthrough Videos
    |--------------------------------------------------------------------------
    |
    | A recording shown above a client's instructions, keyed by client: a URL,
    | or an array with "src" and optional "poster" and "caption". Use a format
    | every browser plays, such as H.264 MP4.
    |
    */

    'videos' => [
        // 'claude-desktop' => ['src' => '/videos/claude-desktop.mp4', 'caption' => 'Connecting from Claude Desktop'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Discovery
    |--------------------------------------------------------------------------
    |
    | Servers are discovered from the Laravel MCP registrar automatically. The
    | registrar stores closures rather than class names, so if a server is not
    | picked up you can map its key (route URI or local handle) to its class
    | here. "only" and "except" accept either keys or class names.
    |
    */

    'servers' => [
        // 'mcp/weather' => App\Mcp\Servers\Weather::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public URLs
    |--------------------------------------------------------------------------
    |
    | The URL clients connect to, keyed like "servers", for a web server that
    | sits behind a gateway or proxy on another path. Without an entry the
    | route's own URL is documented.
    |
    */

    'urls' => [
        // 'mcp/weather' => 'https://example.com/mcp',
    ],

    'only' => [],

    'except' => [],

    /*
    |--------------------------------------------------------------------------
    | Install Instructions
    |--------------------------------------------------------------------------
    |
    | Which clients get a ready-to-copy install snippet on the page, in the
    | order they should appear.
    |
    */

    'clients' => [
        'claude-code',
        'claude-desktop',
        'chatgpt',
        'codex',
        'cursor',
        'vscode',
        'zed',
        'windsurf',
        'deepseek',
    ],

];
