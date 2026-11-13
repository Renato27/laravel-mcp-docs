<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Symfony\Component\HttpFoundation\IpUtils;

class DocsAccess
{
    /**
     * Mode in which anyone who can reach the URL may read the page.
     */
    public const PUBLIC = 'public';

    /**
     * Creates the access policy reader over the application configuration.
     *
     * @param  Config  $config
     * @return void
     */
    public function __construct(protected Config $config)
    {
    }

    /**
     * Whether the page is open to anyone.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->config->get('mcp-docs.access.mode') === self::PUBLIC;
    }

    /**
     * Addresses and CIDR ranges that may read the page when it is restricted.
     *
     * @return list<string>
     */
    public function allowedIps(): array
    {
        $ips = $this->config->get('mcp-docs.access.allowed_ips', []);

        if (! is_array($ips)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $ip): string => is_string($ip) ? trim($ip) : '', $ips),
            fn (string $ip): bool => $ip !== '',
        ));
    }

    /**
     * Whether a request from the given address may read the page.
     *
     * @param  string|null  $ip
     * @return bool
     */
    public function allows(?string $ip): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        if ($ip === null || $ip === '') {
            return false;
        }

        $allowed = $this->allowedIps();

        return $allowed !== [] && IpUtils::checkIp($ip, $allowed);
    }

    /**
     * A short description of the current policy, shown on the page itself.
     *
     * @return string
     */
    public function label(): string
    {
        if ($this->isPublic()) {
            return 'Public';
        }

        $count = count($this->allowedIps());

        return $count === 1
            ? 'Restricted to 1 address'
            : "Restricted to {$count} addresses";
    }

    /**
     * A sentence explaining who can read the page and where to change it.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->isPublic()
            ? 'Anyone who can reach this URL can read this page.'
            : 'This page is limited to allowed networks. Change it in config/mcp-docs.php under `access`.';
    }
}
