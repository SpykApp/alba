<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Themes;

/** Extend this and override only what your theme changes. */
abstract class BaseTheme implements Theme
{
    public function tokens(): array
    {
        return [];
    }

    public function cssFile(): ?string
    {
        return null;
    }

    public function viewsPath(): ?string
    {
        return null;
    }

    public function assetsPath(): ?string
    {
        return null;
    }

    public function logo(): string|array|null
    {
        return null;
    }
}
