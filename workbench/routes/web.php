<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use LaravelMcpDocs\Tests\Fixtures\Servers\OrdersServer;

// A local (STDIO) server and a web (HTTP) one, so the page shows both
// transports and the install snippets that differ between them.
Mcp::local('orders', OrdersServer::class);
Mcp::web('mcp/orders', OrdersServer::class);

Route::redirect('/', '/mcp/docs');
