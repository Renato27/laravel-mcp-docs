<?php

declare(strict_types=1);

namespace LaravelMcpDocs\Install;

enum OperatingSystem: string
{
    /**
     * Apple macOS.
     */
    case MacOs = 'macos';
    /**
     * Microsoft Windows.
     */
    case Windows = 'windows';
    /**
     * Linux.
     */
    case Linux = 'linux';

    /**
     * The platform's display name.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MacOs => 'macOS',
            self::Windows => 'Windows',
            self::Linux => 'Linux',
        };
    }

    /**
     * Whether this is Windows.
     *
     * @return bool
     */
    public function isWindows(): bool
    {
        return $this === self::Windows;
    }

    /**
     * A path under the user's home directory, written the way this OS writes it.
     *
     * Only for paths that are a straight translation; one that moves to another directory on Windows is named per platform.
     *
     * @param  string  $path
     * @return string
     */
    public function home(string $path): string
    {
        $path = ltrim($path, '~/');

        return $this->isWindows()
            ? '%USERPROFILE%\\'.str_replace('/', '\\', $path)
            : '~/'.$path;
    }

    /**
     * The modifier a client's shortcuts are written with on this platform.
     *
     * @return string
     */
    public function modifier(): string
    {
        return $this === self::MacOs ? 'Cmd' : 'Ctrl';
    }
}
