<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Files;

use RuntimeException;
use SpykraLabs\Alba\Support\Context;

/** Keeps file actions inside the app base path. */
final class Guard
{
    public static function inside(Context $ctx, string $relative): string
    {
        $path = $ctx->path($relative);
        $base = rtrim(str_replace('\\', '/', $ctx->alba->basePath), '/').'/';
        $normalized = str_replace('\\', '/', $path);

        if (str_contains($normalized, '..') || ! str_starts_with($normalized.'/', $base) || $normalized.'/' === $base) {
            throw new RuntimeException("Refusing to touch [$relative]: it is outside the application.");
        }

        return $path;
    }
}
