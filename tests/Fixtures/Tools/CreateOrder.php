<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests\Fixtures\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

/**
 * Creates an order from an existing cart.
 *
 * The cart must already carry a delivery address. Idempotent on
 * `idempotency_key`: replaying the same key returns the original order
 * instead of creating a duplicate.
 */
#[Description('Create an order from a cart.')]
#[IsIdempotent]
class CreateOrder extends Tool
{
    /**
     * Answers with a fixed acknowledgement.
     *
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return Response::text('ok');
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
            'cart_id' => $schema->string()->description('Identifier of the cart to convert.')->required(),
            'idempotency_key' => $schema->string()->description('Replay guard for retries.')->required(),
        ];
    }
}
