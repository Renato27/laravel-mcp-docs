<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests\Fixtures\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use LaravelMcpDocs\Tests\Fixtures\Prompts\DraftRefundEmail;
use LaravelMcpDocs\Tests\Fixtures\Resources\OrderPolicy;
use LaravelMcpDocs\Tests\Fixtures\Tools\CreateOrder;
use LaravelMcpDocs\Tests\Fixtures\Tools\SearchOrders;

/**
 * Order management for support agents.
 *
 * Exposes read access to the order book plus a guarded creation path.
 */
#[Name('Orders')]
#[Version('2.1.0')]
#[Instructions('Use this server to look up and create customer orders.')]
class OrdersServer extends Server
{
    /**
     * The tools this server exposes.
     */
    protected array $tools = [
        SearchOrders::class,
        CreateOrder::class,
    ];

    /**
     * The resources this server exposes.
     */
    protected array $resources = [
        OrderPolicy::class,
    ];

    /**
     * The prompts this server exposes.
     */
    protected array $prompts = [
        DraftRefundEmail::class,
    ];
}
