<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LaravelMcpDocs\Support\DocsAccess;

class RestrictDocsAccess
{
    /**
     * Creates the middleware with the access policy it enforces.
     *
     * @param  DocsAccess  $access
     */
    public function __construct(protected DocsAccess $access)
    {
    }

    /**
     * Blocks the documentation page with a 403 unless it is public or the caller's IP is allowed.
     *
     * The service provider always appends this middleware to the docs routes, so editing
     * `mcp-docs.route.middleware` cannot remove it.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->access->allows($request->ip())) {
            return $next($request);
        }

        abort(403, 'The MCP documentation is not public.');
    }
}
