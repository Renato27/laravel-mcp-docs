<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests\Fixtures\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;

/**
 * The refund and cancellation policy agents should quote from.
 */
#[Description('Refund and cancellation policy.')]
class OrderPolicy extends Resource
{
    /**
     * Answers with the policy text.
     *
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return Response::text('Orders may be cancelled within 24 hours.');
    }
}
