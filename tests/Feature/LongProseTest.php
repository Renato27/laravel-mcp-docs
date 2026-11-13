<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use LaravelMcpDocs\Tests\Fixtures\Tools\SearchOrders;

#[Name('Verbose')]
#[Instructions(<<<'MD'
    EricaPRO is a business data platform covering companies in Spain and Portugal.

    ## Tools
    - **search-companies-tool**: The main tool. Search and filter companies.
    - **get-company-tool**: Fetch a single company by identifier.

    ## Guidance
    Always call the search tool before fetching a company. Results are capped
    and ordered by relevance. Do not assume a company exists without searching.

    ## Limits
    The API is rate limited. Batch your lookups where possible.
    MD)]
class VerboseServer extends Server
{
    protected array $tools = [SearchOrders::class];
}

beforeEach(function (): void {
    Mcp::local('verbose', VerboseServer::class);
});

it('renders markdown structure instead of one blob', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('<ul>', false)
        ->assertSee('<li>', false)
        ->assertSee('>Tools</h', false)
        ->assertSee('<strong>search-companies-tool</strong>', false);
});

it('collapses long instructions behind a control', function (): void {
    $this->get('/mcp/docs')
        ->assertSee('is-collapsible', false)
        ->assertSee('data-prose-toggle', false)
        ->assertSee('Show all');
});

it('keeps the full text in the page for search and copy', function (): void {
    // Clamping is visual; the content must still be present and reachable.
    $this->get('/mcp/docs')
        ->assertSee('Batch your lookups where possible.')
        ->assertSee('Do not assume a company exists without searching.');
});

it('wires the toggle to the block it controls', function (): void {
    $page = $this->get('/mcp/docs')->getContent();

    expect($page)->toContain('id="verbose-instructions"')
        ->and($page)->toContain('aria-controls="verbose-instructions"');
});

it('leaves short prose uncollapsed', function (): void {
    $this->get('/mcp/docs')->assertDontSee('id="about-search-orders"', false);
});

it('sizes markdown headings for the block, not the page', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/assets/docs.css');

    // commonmark emits h1/h2 for #/##, which would otherwise inherit the
    // page's own heading sizes and dwarf the surrounding prose.
    expect($css)->toContain('.prose-body h1, .prose-body h2, .prose-body h3,');
});
