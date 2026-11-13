<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests\Fixtures\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Finds orders by customer, status or reference.
 *
 * Results are capped at 50 rows and ordered newest first. Searching by
 * `reference` is an exact match; `customer` matches on name or e-mail.
 */
#[Description('Search orders by customer, status or reference.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchOrders extends Tool
{
    /**
     * Answers with an empty result list.
     *
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return Response::text('[]');
    }

    /**
     * The tool's input schema.
     *
     * @param  JsonSchema  $schema
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer' => $schema->string()->description('Customer name or e-mail.'),
            'status' => $schema->string()->enum(['pending', 'paid', 'shipped'])->description('Order status.'),
            'limit' => $schema->integer()->description('Maximum rows to return.')->default(25),
        ];
    }
}
