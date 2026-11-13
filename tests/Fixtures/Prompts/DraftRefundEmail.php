<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Tests\Fixtures\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * Drafts a refund e-mail in the support team's tone of voice.
 */
#[Description('Draft a refund e-mail for an order.')]
class DraftRefundEmail extends Prompt
{
    /**
     * Answers with the opening of the e-mail.
     *
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return Response::text('Dear customer,');
    }

    /**
     * The prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(name: 'order_id', description: 'The order being refunded.', required: true),
        ];
    }
}
